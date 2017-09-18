<?php

class WkHtml {
    const TO_GIF = 0;
    const TO_JPG = 1;
    const TO_PNG = 2;
    const TO_PDF = 3;

    protected $_command = null;
    protected $_defaults = [
        'type' => 'pdf'
        , 'disable-local-file-access' => true
    ];
    protected $_input = null;
    protected $_filename = null;
    protected $_fileext = 'jpg';
    protected $_output_format = self::TO_JPG;
    protected $_options = [];

    public function __construct(
        string $input
        , array $options
        , int $format = null
    ) {
        array_walk_recursive($options, function(&$value, $key) {
            $value = escapeshellarg($value);
        });

        $this->_input = $input;
        $this->_output_format = $format;
        $this->_filename = tempnam('/tmp', random_int(0, time()));
        switch ($this->_output_format) {
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
        $this->_options = $options;
    }

    /**
     * Generate and output an image using the specified options
     * @return void
     */
    public function generate() {
        $fp = fopen("{$this->_filename}.html", 'w');
        fwrite($fp, $this->_input);
        fclose($fp);

        $this->_generateCmd();
        `{$this->_command}`;
        $this->sendHeaders();
        echo file_get_contents("{$this->_filename}.{$this->_fileext}");
        `rm -f {$this->_filename}.*`;
    }

    /**
     * Send the appropriate content-type header
     * @return void
     */
    public function sendHeaders() {
        switch ($this->_output_format) {
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
    }

    protected function _generateCmd() {
        if (self::TO_PDF === $this->_output_format) {
            $this->_command = 'xvfb-run -- /usr/bin/wkhtmltopdf';
            $this->_addCmdOptions();
            $this->_command .= " {$this->_filename}.html {$this->_filename}.{$this->_fileext}";
        } else {
            $this->_command = 'xvfb-run -- /usr/bin/wkhtmltoimage';
            $this->_addCmdOptions();
            $this->_command .= " --format {$this->_fileext} {$this->_filename}.html {$this->_filename}.{$this->_fileext}";
        }
    }

    protected function _addCmdOptions() {
        foreach($this->_options as $option => $val) {
            // Common optiosn
            switch ($option) {
                // no args
                case "stop-slow-scripts":
                case "no-stop-slow-scripts":
                case "disable-plugins":
                case "enable-plugins":
                case "disable-local-file-access":
                case "enable-local-file-access":
                case "images":
                case "no-images":
                case "disable-javascript":
                case "enable-javascript":
                case "custom-header-propagation":
                case "no-custom-header-propagation":
                case "debug-javascript":
                case "no-debug-javascript":
                    $this->_command .= " --{$option}";
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
                    if (self::TO_PDF === $this->_output_format) {
                        switch ($option) {
                            default:
                                header("HTTP/1.1 400 Bad Request");
                                echo "Invalid option '{$option}'";
                                exit;
                            break;

                            // no args
                            case "collate":
                            case "no-collate":
                            case "grayscale":
                            case "lowquality":
                            case "quiet":
                            case "background":
                            case "no-background":
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
                            case "page-size":
                            case "page-width":
                            case "title":
                            case "page-offset":
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

                            // 1 arg
                            case "crop-h":
                            case "crop-w":
                            case "crop-x":
                            case "crop-y":
                            case "format":
                            case "height":
                            case "width":
                            case "quality":
                                $this->_command .= " --{$option} {$val}";
                            break;
                        }
                    }
                break;
            }
        }
    }
}
