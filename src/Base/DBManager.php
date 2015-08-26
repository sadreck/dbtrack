<?php
namespace DBtrack\Base;

class DBManager
{
    /**
     * Return a list of all the supported databases.
     * @return array
     */
    public function getSupportedDatabases()
    {
        $baseDir = dirname(dirname(__FILE__)) . '/Databases';
        $list = array();
        $files = glob($baseDir . '/*.php');

        foreach ($files as $file) {
            $list[] = pathinfo($file, PATHINFO_FILENAME);
        }
        return $list;
    }
}