<?php

class WkHtml {
    const TO_GIF = 0;
    const TO_JPG = 1;
    const TO_PNG = 2;
    const TO_PDF = 3;

    const TMP_DIR = '/tmp';
    const ISSUE_2231 = '/var/www/lib/css';

    protected $_command = null;
    protected $_defaults = [
        'disable-local-file-access' => true
        , 'disable-smart-width' => true
        , 'quiet' => true
        , 'user-style-sheet' => '/var/www/lib/css/issue-2231.css'
    ];
    protected $_image_defaults = [
    ];
    protected $_pdf_defaults = [
    ];
    protected $_input = null;
    protected $_image = null;
    protected $_filename = null;
    protected $_fileext = 'jpg';
    protected $_format = self::TO_JPG;
    protected $_options = [];

    public function __construct(
        string $input
        , int $format = null
        , array $options = null
    ) {
        $this->setInput($input);
        $this->setFormat($format);
        $this->setOptions($options);
    }

    /**
     * Generate an image using the specified options
     * @return void
     */
    public function generate() {
        $fp = fopen("{$this->getFilename()}.html", 'w');
        fwrite($fp, $this->getInput());
        fclose($fp);

        `{$this->getCommand()}`;
        $this->_image = file_get_contents("{$this->getFilename()}.{$this->getFileExtension()}");

        `rm -f {$this->getFilename()}.*`;
        `rm -f {$this->getFilename()}`;

        return $this;
    }

    /**
     * Send the image data to the client
     * @return WkHtml
     */
    public function send() {
        switch ($this->getFormat()) {
            case self::TO_GIF:
                header('Content-Type: image/gif');
            break;
            case self::TO_JPG:
                header('Content-Type: image/jpeg');
            break;
            case self::TO_PNG:
                header('Content-Type: image/png');
            break;
            case self::TO_PDF:
                header('Content-Type: application/pdf');
            break;
        }
        echo $this->_image;
        return $this;
    }

    /**
     * Get the wkhtml shell command
     * @return string
     */
    protected function getCommand() {
        if (is_null($this->_command)) {
            if (self::TO_PDF === $this->getFormat()) {
                $this->_command = '/usr/bin/wkhtmltopdf';
            } else {
                $this->_command = "/usr/bin/wkhtmltoimage --format {$this->getFileExtension()}";
            }
            $this->_addCmdOptions();
            $this->_command .= " {$this->getFilename()}.html {$this->getFilename()}.{$this->getFileExtension()}";
        }
        return $this->_command;
    }

    /**
     * Get the output file extension
     * @return string
     */
    public function getFileExtension() {
        if (is_null($this->_fileext)) {
            switch ($this->getFormat()) {
                case self::TO_GIF:
                    $this->_fileext = 'gif';
                break;
                case self::TO_JPG:
                    $this->_fileext = 'jpg';
                break;
                case self::TO_PNG:
                    $this->_fileext = 'png';
                break;
                case self::TO_PDF:
                    $this->_fileext = 'pdf';
                break;
            }
        }
        return $this->_fileext;
    }

    /**
     * Get the temporary file name
     * @return string
     */
    public function getFilename() {
        if (is_null($this->_filename)) {
            $this->_filename = tempnam('/tmp', random_int(0, time()));
        }
        return $this->_filename;
    }

    /**
     * Get the output file format
     * @return int
     */
    public function getFormat() {
        return $this->_format;
    }

    /**
     * Get the input HTML
     * @return string
     */
    public function getInput() {
        return $this->_input;
    }

    /**
     * Set the output file format
     * @param int|null $format
     * @return WkHtml
     */
    public function setFormat(int $format = null) {
        if (!is_null($format)) {
            switch ($this->_format) {
                case self::TO_GIF:
                case self::TO_JPG:
                case self::TO_PNG:
                case self::TO_PDF:
                    $this->_format = $format;
                break;

                default:
                    throw new \InvalidArgumentException("Invalid format '{$format}'");
                break;
            }
        }
        return $this;
    }

    /**
     * Set the input HTML
     * @param string $input
     * @return WkHtml
     */
    public function setInput(string $input) {
        // Remove tracking pixels, atches img tags that are size 1x1
        $search = '/<img[^>]*(width=["\']1["\'][^>]*|height=["\']1["\'][^>]*){2}>/im';
        $replace = '<!-- TRACKING PIXEL REMOVED -->';
        $input = preg_replace($search, $replace, $input, -1, $count);

        $this->_input = $input;
        return $this;
    }

    /**
     * Set the wkhtml command options
     * @param array|null $options
     * @return WkHtml
     */
    public function setOptions(array $options = null) {
        if (!is_null($options)) {
            if (!isset($options['allow'])) {
                $options['allow'] = [];
            }
            $options['allow'] = (array) $options['allow'];
            $options['allow'][] = self::TMP_DIR;
            $options['allow'][] = self::ISSUE_2231;
            $options = array_merge($this->_defaults, $options);
            if (self::TO_PDF === $this->getFormat()) {
                $options = array_merge($this->_pdf_defaults, $options);
            } else {
                $options = array_merge($this->_image_defaults, $options);
            }
            array_walk_recursive($options, function(&$value, $key) {
                $value = escapeshellarg($value);
            });
            $this->_options = $options;
        }
        return $this;
    }

    /**
     * Add any options to the shell command
     * @return WkHtml
     */
    protected function _addCmdOptions() {
        foreach($this->_options as $option => $val) {
            // Common optiosn
            switch ($option) {
                // no args
                case "custom-header-propagation":
                case "debug-javascript":
                case "disable-javascript":
                case "disable-local-file-access":
                case "disable-plugins":
                case "disable-smart-width":
                case "enable-javascript":
                case "enable-plugins":
                case "images":
                case "no-custom-header-propagation":
                case "no-debug-javascript":
                case "no-images":
                case "no-stop-slow-scripts":
                case "quiet":
                case "stop-slow-scripts":
                    $this->_command .= " --{$option}";
                break;

                // 1 arg
                case "checkbox-checked-svg":
                case "checkbox-svg":
                case "cookie-jar":
                case "encoding":
                case "javascript-delay":
                case "load-error-handling":
                case "minimum-font-size":
                case "password":
                case "proxy":
                case "radiobutton-checked-svg":
                case "radiobutton-svg":
                case "user-style-sheet":
                case "username":
                case "window-status":
                case "zoom":
                    $this->_command .= " --{$option} {$val}";
                break;

                // 1 arg, repeatable
                case "allow":
                    if (!is_array($val)) {
                        $this->_command .= " --{$option} {$val}";
                    } else {
                        foreach ($val as $aval) {
                            $this->_command .= " --{$option} {$aval}";
                        }
                    }
                break;

                // 2 args, repeatable
                case "cookie":
                case "custom-header":
                case "post":
                case "post-file":
                    if (!is_array($val)) {
                        header("HTTP/1.1 400 Bad Request");
                        echo "Invalid value '{$val}' for option '{$option}'";
                        exit;
                    } else {
                        foreach ($val as $aval) {
                            $this->_command .= " --{$option} {$aval[0]} {$aval[1]}";
                        }
                    }
                break;

                default:

                    // PDF options
                    if (self::TO_PDF === $this->getFormat()) {
                        switch ($option) {
                            default:
                                header("HTTP/1.1 400 Bad Request");
                                echo "Invalid option '{$option}'";
                                exit;
                            break;

                            // no args
                            case "background":
                            case "collate":
                            case "grayscale":
                            case "lowquality":
                            case "no-background":
                            case "no-collate":
                                $this->_command .= " --{$option}";
                            break;

                            // 1 arg
                            case "copies":
                            case "dpi":
                            case "margin-bottom":
                            case "margin-left":
                            case "margin-right":
                            case "margin-top":
                            case "orientation":
                            case "output-format":
                            case "page-height":
                            case "page-offset":
                            case "page-size":
                            case "page-width":
                            case "title":
                                $this->_command .= " --{$option} {$val}";
                            break;
                        }

                    // Image options
                    } else {
                        switch ($option) {
                            default:
                                header("HTTP/1.1 400 Bad Request");
                                echo "Invalid option '{$option}'";
                                exit;
                            break;

                            case "disable-smart-width":
                                $this->_command .= " --{$option}";
                            break;

                            // 1 arg
                            case "crop-h":
                            case "crop-w":
                            case "crop-x":
                            case "crop-y":
                            case "format":
                            case "height":
                            case "quality":
                            case "width":
                                $this->_command .= " --{$option} {$val}";
                            break;
                        }
                    }
                break;
            }
        }
        return $this;
    }
}
