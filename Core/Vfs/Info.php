<?php

/**
 * @package Core_Vfs
 * @author  P.Matvienko
 * @project W.A.C.
 *
 */
class Core_Vfs_Info
{
    const SIZE_B   = 'b';
    const SIZE_KB  = 'Kb';
    const SIZE_MB  = 'Mb';
    const SIZE_HUM = 'Human';

    protected $_data = array();

    /**
     * Constructor.
     *
     * @param mixed $data File Info data to set.
     */
    public function __construct($data)
    {
        $this->_data = $data;
    }

    /**
     * Gets a full path to a file.
     *
     * @return string
     */
    public function path()
    {
        $path      = $this->directory . DIRECTORY_SEPARATOR . $this->name;
        $extension = $this->extension;
        if (!empty($extension)) {
            $path .= '.' . $extension;
        }

        return $path;
    }

    /**
     * Gets a file base name.
     *
     * @return string
     */
    public function baseName()
    {
        $name      = $this->name;
        $extension = $this->extension;
        if (!empty($extension)) {
            $name .= '.' . $extension;
        }

        return $name;
    }

    /**
     * Gets a file size.
     *
     * @param string $units Available units b - bytes, Kb, Mb.
     *
     * @return float|null
     */
    public function size($units = self::SIZE_B)
    {
        $size = $this->size;
        if (empty($size)) {
            return null;
        }
        switch ($units) {
            case self::SIZE_KB:
                $size = round(($size / 1024), 2);
                break;

            case self::SIZE_MB:
                $size = round(($size / 1048576), 2);
                break;

            case self::SIZE_HUM:
                $size = round(($this->size / 1048576), 2);
                if ($size < 1) {
                    $size = round(($this->size / 1024), 2);
                    if ($size < 1) {
                        $size = strval($this->size) . ' B';
                    } else {
                        $size = strval($size) . ' Kb';
                    }
                } else {
                    $size = strval($size) . ' Mb';
                }
                break;

            default:
                $size = $size;
                break;
        }

        return $size;
    }

    /**
     * Gets a RAW Permissions data.
     *
     * @return integer
     */
    public function permissions()
    {
        return $this->permissions;
    }

    /**
     * Gets a permissions in octal representation.
     *
     * @return string
     */
    public function permissionsOctal()
    {
        $permissions = $this->permissions;
        if (empty($permissions)) {
            return null;
        }

        return sprintf("0%o", 0777 & ($permissions));
    }

    /**
     * Gets a permissions in human readable representation.
     *
     * @return string
     */
    public function permissionsHuman()
    {
        $p = $this->permissions;
        if (empty($p)) {
            return null;
        }
        $ts  = array
        (
            0140000 => 'ssocket',
            0120000 => 'llink',
            0100000 => '-file',
            0060000 => 'bblock',
            0040000 => 'ddir',
            0020000 => 'cchar',
            0010000 => 'pfifo'
        );
        $t   = decoct($p & 0170000);
        $str = (array_key_exists(octdec($t), $ts)) ? $ts[octdec($t)]{0} : 'u';
        $str .= (($p & 0x0100) ? 'r' : '-') . (($p & 0x0080) ? 'w' : '-');
        $str .= (($p & 0x0040) ? (($p & 0x0800) ? 's' : 'x') : (($p & 0x0800) ? 'S' : '-'));
        $str .= (($p & 0x0020) ? 'r' : '-') . (($p & 0x0010) ? 'w' : '-');
        $str .= (($p & 0x0008) ? (($p & 0x0400) ? 's' : 'x') : (($p & 0x0400) ? 'S' : '-'));
        $str .= (($p & 0x0004) ? 'r' : '-') . (($p & 0x0002) ? 'w' : '-');
        $str .= (($p & 0x0001) ? (($p & 0x0200) ? 't' : 'x') : (($p & 0x0200) ? 'T' : '-'));

        return $str;
    }

    /**
     * Gets a creation date.
     *
     * @param null|string $dateMask Optional. Can be used to represent data formated by date function.
     *
     * @return string
     */
    public function created($dateMask = null)
    {
        return $this->_dateFormat($this->creationTime, $dateMask);
    }

    /**
     * Gets a last access date.
     *
     * @param null|string $dateMask Optional. Can be used to represent data formated by date function.
     *
     * @return string
     */
    public function lastAccess($dateMask = null)
    {
        return $this->_dateFormat($this->lastAccessTime, $dateMask);
    }

    /**
     * Gets a edit date.
     *
     * @param null|string $dateMask Optional. Can be used to represent data formated by date function.
     *
     * @return string
     */
    public function lastEdit($dateMask = null)
    {
        return $this->_dateFormat($this->lastEditTime, $dateMask);
    }

    /**
     * Formatting dates by given mask.
     *
     * @param integer     $value Time to format.
     * @param null|string $mask  Mask to format, for function date.
     *
     * @return string
     */
    protected function _dateFormat($value, $mask = null)
    {
        if (!empty($value) && !empty($mask)) {
            $value = date($mask, $value);
        }

        return $value;
    }

    /**
     * Magick getter.
     *
     * @param string $name Name of parameter.
     *
     * @return null|integer|string
     */
    public function __get($name)
    {
        if (!isset($this->_data[$name])) {
            return null;
        }

        return $this->_data[$name];
    }

    /**
     * This method allows to get data by calling it like a class methods.
     *
     * @param string $name   Parameter name.
     * @param mixed  $params Parameters for called function. Not used now.
     *
     * @return mixed
     */
    public function __call($name, $params)
    {
        switch ($name) {
            case 'writable':
                $name = 'isWritable';
                break;

            case 'readable':
                $name = 'isReadable';
                break;

            default:
                //Nothing to do here now.
                break;
        }
        return $this->$name;
    }
}