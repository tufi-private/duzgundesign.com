<?
/*
	Contacular - a super simple contact form code base for web developers
	Copyright (C) 2009-2010 Jordan Hall

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.

	*** Contacular 0.151 'clean' ***
*/

// *** Class Declaration ***

class ContacularForm
{
    // *** Member Variable Declaration ***

    private $attribution = true;
    private $advancedValidation = true;
    private $fields = array();
    private $recipients = array();
    private $seperator;
    private $errors = array();
    private $recaptchaPublicKey = null;
    private $recaptchaPrivateKey = null;
    private $disallowedTypes
        = array("exe", "dll", "vbs", "js", "bat", "bin", "php", "phps", "asp",
                "aspx", "js", "scr");
    private $language;
    private $localisedStrings = array();

    const DEFAULT_LANG = 'tr';

    // *** Public Member Functions ***

    /*
    Function: Constructor
    Purpose: Called upon instantiated to perform initial setup of the form fields if a specific '$type' is passed.
    Arguments:
        $type - Determines the type of form to generate
        $width - Overrides default width of form fields (in pixels) - useful for making form fit (in sidebars for example)
    */

    public function __construct($type = false, $width = null)
    {
        if ($type) {
            $this->setup($type, $width);
        }
    }

    /*
    Function: addRecipient
    Purpose: Used to add a new e-mail address to the list of e-mail recipients associated with this form
    Arguments:
        $recipient - E-mail address recipient to associate with this form
    */
    public function addRecipient($recipient)
    {
        $this->recipients[] = $recipient;
    }

    /*
    Function: setSeperator
    Purpose: Used to set the character or string which seperates the form field names from the data entry elements
    Arguments:
        $seperator - Character or string used to seperate form field names from data entry elements (e.g. ':', ' -' or ':-')
    */
    public function setSeperator($seperator)
    {
        $this->seperator = $seperator;
    }

    /*
    Function: setAttribution
    Purpose: Sets whether or not the attribution text, 'Powered by Contacular', is displayed on generated forms
    Recommendation: Leaving this text displayed gives credit to the original author, and helps promote Contacular
    Arguments:
        $attribution - Boolean used to determine whether or not the 'Powered by Contacular' text is displayed
    */
    public function setAttribution($attribution = true)
    {
        $this->attribution = $attribution;
    }

    /*
    Function: setDisallowedTypes
    Purpose: Allows you to override thhe default array of disallowed file types for uploaded files
    Arguments:
        $disallowedTypes - Array of file extensions which are disallowed for the upload of files.
    */
    public function setDisallowedTypes($disallowedTypes)
    {
        $this->disallowedTypes = $disallowedTypes;
    }

    /*
    Function: setRecaptchaPublicKey
    Purpose: Sets the Recaptcha public key obtained from recaptcha.net and enables Recaptcha checking
    Arguments:
        $recaptchaPublicKey - Recaptcha public key obtained from recaptcha.net
    */
    public function setRecaptchaPublicKey($recaptchaPublicKey)
    {
        $this->recaptchaPublicKey = $recaptchaPublicKey;
    }

    /*
    Function: setRecaptchaPrivateKey
    Purpose: Sets the Recaptcha public key obtained from recaptcha.net and enables Recaptcha checking
    Arguments:
        $recaptchaPublicKey - Recaptcha public key obtained from recaptcha.net
    */
    public function setrecaptchaPrivateKey($recaptchaPrivateKey)
    {
        $this->recaptchaPrivateKey = $recaptchaPrivateKey;
    }

    /*
    Function: setAdvancedValidation
    Purpose: Sets whether or not advanced validation checking is performed (such as DNS A record checking on e-mail address validation)
    Recommendation: Enabled by default (recommended) to ensure maximum validation, but could potentially cause false positives.
    Arguments:
        $advancedValidation - Boolean used to determine whether or not advanced validation techniques are used
    */
    public function setAdvancedValidation($advancedValidation = true)
    {
        $this->advancedValidation = $advancedValidation;
    }

    /*
    Function: getCode
    Purpose: Generate the defined form HTML code and return it for output or other use
    Returns: String - Generated HTML code for form
    Arguments:
        None
    */
    public function getCode()
    {
        // Check sanity of ContacularForm object
        if ($sanity = $this->sanityCheck()) {
            return $sanity;
        }

        // Generate HTML code
        $url = $this->getURL();
        $code = $this->attribution
            ? "\n<!-- Contacular Form Start (http://contacular.co.uk/) -->\n"
            : '';

        $code .= "<form method=\"post\" action=\"" . $url
            . "\" name=\"contacularform\" enctype=\"multipart/form-data\">";
        $code .= "<table>";
        foreach ($this->fields as $field) {
            $code .= "<tr>";
            $code
                .=
                "<td><label for=\"" . $field['name'] . "\">" . $field['label']
                    . $this->seperator . " </label></td>";
            $code .= "<td>";
            $code .= $this->getFormFieldText($field);
            $code .= "</td>";
            $code .= "</tr>";
        }
        if ($this->recaptchaPublicKey && $this->recaptchaPrivateKey) {
            require_once('recaptchalib.php');
            $code .= "<tr><td></td><td>";
            $code .= recaptcha_get_html($this->recaptchaPublicKey);
            $code .= "</td></tr>";
        }
        $code
            .=
            "<tr><td></td><td><input style=\"font-family: inherit;\" type=\"submit\" name=\"contacularform_submit\" value=\"Send\" /> "
                . $this->getAttributionText() . "</td></tr>";
        $code .= "</table>";
        $code .= "</form>";
        if ($this->attribution) {
            $code .= "\n<!-- Contacular Form End -->\n";
        }
        return $code;
    }

    /*
    Function: processResponse
    Purpose: Processes the response from the form POST and deals with any necessary validation and output such as e-mailing out
    Returns: Boolean - true if form POST has been handled by this function or false if no contacularform submission was detected
    Arguments:
        $post - The $_POST PHP super global array from the page to which the form values are sent, defaults to '$_POST'
        $files - The $_FILES PHP super global array from the page to which the form values are sent, defaults to '$_FILES'
    */
    public function processResponse($post = null, $files = null)
    {
        // Set defaults
        if (!$post) {
            $post = $_POST;
        }
        if (!$files) {
            $files = $_FILES;
        }

        if (!array_key_exists('contacularform_submit', $post)) {
            // If no contacularform_submit was posted then the post did not come from a contacular form submission, thus we have nothing to do.
            return false;
        }

        // Check sanity of ContacularForm object
        if ($this->sanityCheck()) {
            return false;
        }

        // Perform validation
        foreach ($this->fields as $field) {
            if ($field['type'] == "email") {
                if (!$this->validateEmail($post[$field['name']])) {
                    $this->addError(
                        $this->getLocalisedString('errInvalidEmail')
                    );
                }
            } elseif ($field['type'] == "mandatorytext"
                || $field['type'] == "mandatorytextarea"
            ) {
                if (!trim($post[$field['name']])) {
                    $this->addError(
                        sprintf(
                            $this->getLocalisedString(
                                'errMissingRequiredField'
                            ), $field['label']
                        )
                    );
                }
            } elseif ($field['type'] == "file") {
                $fileExtension = $this->getFileExtension(
                    $files[$field['name']]['name']
                );
                if (in_array($fileExtension, $this->disallowedTypes)) {
                    $this->addError(
                        sprintf(
                            $this->getLocalisedString(
                                'errInvalidFileExtension'
                            ), $fileExtension
                        )
                    );
                }

            }
        }
        if ($this->recaptchaPublicKey && $this->recaptchaPrivateKey) {
            require_once('recaptchalib.php');
            $resp = recaptcha_check_answer(
                $this->recaptchaPrivateKey, $_SERVER["REMOTE_ADDR"],
                $_POST["recaptcha_challenge_field"],
                $_POST["recaptcha_response_field"]
            );
            if (!$resp->is_valid) {
                $this->addError(
                    $this->getLocalisedString('errIncorrectReCaptcha')
                );
            }
        }
        if (count($this->errors) != 0) {
            return false;
        }

        // Build e-mail content
        if ($this->attribution) {
            $contacularName = "Contacular";
        } else {
            $contacularName = "";
        }
        $subject = sprintf(
            self::defaultSubject, $contacularName, $_SERVER['SERVER_NAME']
        );
        $body = self::defaultBodyIntro;

        foreach ($this->fields as $field) {
            if ($field['type'] == "file") {
                continue;
            }
            if (!$post[$field['name']] && $field['type'] == "checkbox") {
                $post[$field['name']] = "Not ticked";
            }
            $body
                .= $field['label'] . ": " . stripslashes($post[$field['name']])
                . "\n";
        }
        $from = $this->getFromEmail();
        $from_name = $_SERVER['SERVER_NAME'];

        // Include PHP mailer class
        require_once "../PHPMailer_v5.1/class.phpmailer.php";

        // Send out e-mail(s)
        foreach ($this->recipients as $recipient) {
            $mail = new PHPMailer();
            $mail->SetLanguage($this->language);
            $mail->AddAddress($recipient);
            $mail->SetFrom($from, $from_name);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->WordWrap = 50;

            foreach ($files as $file) {
                $mail->AddAttachment($file['tmp_name'], $file['name']);
            }

            $mail->Send();
        }
        return true;
    }

    /*
    Function: getErrors
    Purpose: After calling processResponse, this function can be called to retrieve any validation errors
    Returns: String - a list of validation errors (if any) seperated by a <br/> tag
    Arguments:
        None
    */
    public function getErrors()
    {
        $output = '';
        foreach ($this->errors as $error) {
            $output .= $error . "<br/>";
        }
        return $output;
    }

    /*
    Function: addField
    Purpose: Adds a field to the Contacular form object
    Arguments:
        $name - Internal identifier of form field
        $label - Human readably, friendly name of form field
        $type - Contacular form field type
        $height - Height of form field, defaults to 25 pixels
        $width - Width of form field, defaults to 250 pixels
        $options - Array of options for use with 'select' form field type
    */
    public function addField(
        $name, $label, $type = "text", $height = null, $width = null,
        $options = array()
    )
    {
        if (!$height) {
            $height = 25;
        }
        if (!$width) {
            $width = 250;
        }
        $newField = & $this->fields[];
        $newField['name'] = $name;
        $newField['label'] = $label;
        $newField['type'] = $type;
        $newField['width'] = $width;
        $newField['height'] = $height;
        $newField['options'] = $options;
    }

    // *** Private Member Functions ***

    private function setup($type, $width = null)
    {
        switch ($type) {
            case "simple":
                $this->addField(
                    "from_name", "Name", "mandatorytext", null, $width
                );
                $this->addField("from_email", "E-mail", "email", null, $width);
                $this->addField(
                    "message", "Message", "mandatorytextarea", 100, $width
                );
                break;

            case "simplesubject":
                $this->addField(
                    "from_name", "Name", "mandatorytext", null, $width
                );
                $this->addField(
                    "subject", "Subject", "mandatorytext", null, $width
                );
                $this->addField("from_email", "E-mail", "email", null, $width);
                $this->addField(
                    "message", "Message", "mandatorytextarea", 100, $width
                );
                break;

            case "simpleresponse":
                $this->addField(
                    "from_name", "Name", "mandatorytext", null, $width
                );
                $this->addField("from_email", "E-mail", "email", null, $width);
                $this->addField(
                    "message", "Message", "mandatorytextarea", 100, $width
                );
                $this->addField(
                    "response_desired", "Response desired", "checkbox"
                );
                break;

            case "callback":
                $this->addField(
                    "from_name", "Name", "mandatorytext", null, $width
                );
                $this->addField(
                    "from_telephone", "Telephone", "mandatorytext", $width
                );
                break;

            case "enquiry":
                $this->addField(
                    "from_title", "Title", "select", null, 70,
                    array("Mr", "Mrs", "Miss", "Dr")
                );
                $this->addField(
                    "from_firstname", "First name", "mandatorytext", null,
                    $width
                );
                $this->addField(
                    "from_lastname", "Surname", "mandatorytext", null, $width
                );
                $this->addField(
                    "from_telephone", "Telephone", "text", null, $width
                );
                $this->addField("from_email", "E-mail", "email", null, $width);
                $this->addField(
                    "enquiry", "Enquiry", "mandatorytextarea", 100, $width
                );
                break;

            case "cataloguerequest":
                $this->addField(
                    "from_title", "Title", "select", null, 70,
                    array("Mr", "Mrs", "Miss", "Dr")
                );
                $this->addField(
                    "from_firstname", "First name", "mandatorytext", null,
                    $width
                );
                $this->addField(
                    "from_lastname", "Surname", "mandatorytext", null, $width
                );
                $this->addField(
                    "from_telephone", "Telephone", "text", null, $width
                );
                $this->addField("from_email", "E-mail", "email", null, $width);
                $this->addField(
                    "addressline1", "Address Line 1", "mandatorytext", null,
                    $width
                );
                $this->addField(
                    "addressline2", "Address Line 2", "text", null, $width
                );
                $this->addField(
                    "addressline3", "Address Line 3", "text", null, $width
                );
                $this->addField("city", "City", "mandatorytext", null, $width);
                $this->addField(
                    "county", "County / State", "mandatorytext", null, $width
                );
                $this->addField(
                    "postcode", "Post / ZIP Code", "mandatorytext", null, $width
                );
                $this->addField(
                    "country", "Country", "mandatorytext", null, $width
                );
                break;

            case "contact":
                $this->addField(
                    "from_name", "Name", "mandatorytext", null, $width
                );
                $this->addField("from_email", "E-mail", "email", null, $width);
                $this->addField(
                    "from_telephone", "Telephone", "text", null, $width
                );
                $this->addField(
                    "message", "Message", "mandatorytextarea", 100, $width
                );
                break;

            case "contactresponse":
                $this->addField(
                    "from_name", "Name", "mandatorytext", null, $width
                );
                $this->addField("from_email", "E-mail", "email", null, $width);
                $this->addField(
                    "from_telephone", "Telephone", "text", null, $width
                );
                $this->addField(
                    "message", "Message", "mandatorytextarea", 100, $width
                );
                $this->addField(
                    "response_desired", "Response desired", "checkbox"
                );
                break;

            case "companycontact":
                $this->addField(
                    "from_name", "Name", "mandatorytext", null, $width
                );
                $this->addField("from_email", "E-mail", "email", null, $width);
                $this->addField(
                    "from_company", "Company", "text", null, $width
                );
                $this->addField(
                    "from_telephone", "Telephone", "text", null, $width
                );
                $this->addField(
                    "message", "Message", "mandatorytextarea", 100, $width
                );
                break;

            case "companycontactreferrer":
                $this->addField(
                    "from_name", "Name", "mandatorytext", null, $width
                );
                $this->addField("from_email", "E-mail", "email", null, $width);
                $this->addField(
                    "from_company", "Company", "text", null, $width
                );
                $this->addField(
                    "from_telephone", "Telephone", "text", null, $width
                );
                $this->addField(
                    "referrer", "How did you find us?", "select", null, $width,
                    array("Not sure / Do not wish to say",
                          "Link from another site", "Search engine",
                          "Recommended by a friend", "E-mail campaign",
                          "Advert (Internet)", "Advert (Paper-based)", "Other")
                );
                $this->addField(
                    "referrer_details", "Details on how you found us", "text",
                    null, $width
                );
                $this->addField(
                    "message", "Message", "mandatorytextarea", 100, $width
                );
                break;

            case "comment":
                $this->addField(
                    "from_name", "Name", "mandatorytext", null, $width
                );
                $this->addField("from_email", "E-mail", "email", null, $width);
                $this->addField(
                    "from_website", "Website", "text", null, $width
                );
                $this->addField(
                    "message", "Message", "mandatorytextarea", 100, $width
                );
                break;

            case "development":
                $this->addField(
                    "from_name", "Name", "mandatorytext", null, $width
                );
                $this->addField("from_email", "E-mail", "email", null, $width);
                $this->addField(
                    "from_subject", "Subject", "select", null, $width,
                    array("General", "Bug Report", "Feature Request")
                );
                $this->addField(
                    "message", "Message", "mandatorytextarea", 100, $width
                );
                break;

            case "simplenewsletter":
                $this->addField("from_email", "E-mail", "email", null, $width);
                $this->addField(
                    "subscribe_to_newsletter", "Subscribe to newsletter",
                    "checkbox"
                );
                break;

            case "newsletter":
                $this->addField(
                    "from_name", "Name", "mandatorytext", null, $width
                );
                $this->addField("from_email", "E-mail", "email", null, $width);
                $this->addField(
                    "subscribe_to_newsletter", "Subscribe to newsletter",
                    "checkbox"
                );
                break;

            case "submitcv":
                $this->addField(
                    "from_name", "Name", "mandatorytext", null, $width
                );
                $this->addField("from_email", "E-mail", "email", null, $width);
                $this->addField(
                    "from_telephone", "Telephone", "text", null, $width
                );
                $this->addField("cv", "Submit CV", "file", null, $width);
                break;

            case "submitresume":
                $this->addField(
                    "from_name", "Name", "mandatorytext", null, $width
                );
                $this->addField("from_email", "E-mail", "email", null, $width);
                $this->addField(
                    "from_telephone", "Telephone", "text", null, $width
                );
                $this->addField("cv", "Submit Resume", "file", null, $width);
                break;

            case "submitimage":
                $this->addField(
                    "from_name", "Name", "mandatorytext", null, $width
                );
                $this->addField("from_email", "E-mail", "email", null, $width);
                $this->addField("image", "Submit image", "file", null, $width);
                break;

            case "submitdocument":
                $this->addField(
                    "from_name", "Name", "mandatorytext", null, $width
                );
                $this->addField("from_email", "E-mail", "email", null, $width);
                $this->addField(
                    "document", "Submit document", "file", null, $width
                );
                break;

            case "custom":
                break;

            default:
                if ($this->attribution) {
                    $contacularName = "Contacular";
                } else {
                    $contacularName = "System";
                }
                echo$contacularName . " error: Supplied form type '" . $type
                    . "' is not valid.";
                break;
        }
    }

    private function getFormFieldText($field)
    {
        switch ($field['type']) {
            case "textarea":
            case "mandatorytextarea":
                return "<textarea style=\"width: " . $field['width']
                    . "px; height: " . $field['height'] . "px;\" name=\""
                    . $field['name'] . "\" id=\"" . $field['name']
                    . "\" ></textarea>";
                break;

            case "checkbox":
                return "<input name=\"" . $field['name'] . "\" id=\""
                    . $field['name']
                    . "\" type=\"checkbox\" value=\"Ticked\" />";
                break;

            case "select":
                return $this->getSelectFormFieldText($field, $field['options']);

            case "email":
                return
                    "<input style=\" width: " . $field['width'] . "px; height: "
                        . $field['height'] . "px;\" type=\"text\" name=\""
                        . $field['name'] . "\" id=\"" . $field['name']
                        . "\" />";
                break;

            case "mandatorytext":
                return
                    "<input style=\" width: " . $field['width'] . "px; height: "
                        . $field['height'] . "px;\" type=\"text\" name=\""
                        . $field['name'] . "\" id=\"" . $field['name']
                        . "\" />";
                break;

            default:
                return
                    "<input style=\" width: " . $field['width'] . "px; height: "
                        . $field['height'] . "px;\" type=\"" . $field['type']
                        . "\" name=\"" . $field['name'] . "\" id=\""
                        . $field['name'] . "\" />";
                break;
        }
    }

    private function getSelectFormFieldText($field, $options)
    {
        $code = "<select style=\"width: " . $field['width'] . "px; height: "
            . $field['height'] . "px;\" name=\"" . $field['name'] . "\" id=\""
            . $field['name'] . "\">";
        foreach ($options as $option) {
            $code
                .= "<option value=\"" . $option . "\">" . $option . "</option>";
        }
        $code .= "</select>";
        return $code;
    }

    private function getAttributionText()
    {
        if ($this->attribution) {
            return "<span style=\"font-size: 75%;\">Powered by <a href=\"http://contacular.co.uk/\" target=\"_blank\" title=\"Contacular contact form\">Contacular</a></span>";
        }
        return;
    }

    private function getFromEmail()
    {
        if ($this->attribution) {
            return "contacularbot@" . $_SERVER['SERVER_NAME'];
        } else {
            return "formresponse@" . $_SERVER['SERVER_NAME'];
        }
    }

    private function sanityCheck()
    {
        if ($this->attribution) {
            $contacularName = "Contacular";
        } else {
            $contacularName = "System";
        }
        if (!$this->fields) {
            return
                $contacularName
                . " error: No field(s) were defined for this form.";
        }
        if (!$this->recipients) {
            return $contacularName
                . " error: No recipient(s) were defined for this form.";
        }
    }

    private function validateEmail($email)
    {
        $pattern = '/^([a-z0-9])(([-a-z0-9._])*([a-z0-9]))*\@([a-z0-9])' .
            '(([a-z0-9-])*([a-z0-9]))+'
            . '(\.([a-z0-9])([-a-z0-9_-])?([a-z0-9])+)+$/i';
        if (!preg_match($pattern, $email)) {
            return false;
        } else {
            if ($this->advancedValidation) {
                $email_parts = explode("@", $email);
                $domain = $email_parts[1];
                return checkdnsrr($domain, "A");
            } else {
                return true;
            }
        }
    }

    private function addError($text)
    {
        $this->errors[] = $text;
    }

    private function getURL()
    {
        $url = 'http';
        if ($_SERVER["HTTPS"] == "on") {
            $url .= "s";
        }
        $url .= "://";
        if ($_SERVER["SERVER_PORT"] != "80") {
            $url .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"]
                . $_SERVER["REQUEST_URI"];
        } else {
            $url .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
        }
        return $url;
    }

    private function getFileExtension($filename)
    {
        return substr(strrchr($filename, '.'), 1);
    }


    /**
     * Returns a message in the appropriate language.
     *
     * @access private
     *
     * @param $key
     *
     * @return string
     */
    private function getLocalisedString($key)
    {
        if (count($this->localisedStrings) < 1) {
            $this->setLanguage(self::DEFAULT_LANG); // set the default language
        }

        return isset($this->localisedStrings[$key])
            ? $this->language[$key]
            : 'Language string failed to load: ' . $key;
    }

    /**
     * @param string $langcode
     * @param string $langPath
     */
    public function setLanguage($langcode = 'en', $langPath = 'lang/')
    {
        $defaultStrings = array(
            'errInvalidEmail' => "This e-mail address does not appear to be valid.",
            'errMissingRequiredField' => "The field '%s' is required.",
            'errInvalidFileExtension' => "The file extension '%s' is not allowed.",
            'errIncorrectReCaptcha' => "The reCAPTCHA was not entered correctly.",
            'defaultSubject' => "Response from %s Form at %s",
            'defaultBodyIntro' => "Details from the form are shown below.\n\n",
        );
        $this->language = $langcode;

        if ($langcode != 'en') {
            $langFile = $langPath . 'lang-' . $langcode . '.inc.php';
            if (file_exists($langFile) && is_readable($langFile)) {
                $localised = require
                    $langPath . 'lang-' . $langcode . '.inc.php';
                $this->localisedStrings = $localised + $defaultStrings;
            }
        } else {
            $this->localisedStrings = $defaultStrings;
        }
    }
}