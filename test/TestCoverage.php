<?php
/**
 * @copyright (c) 2019 Bourdercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * @link https://www.mediawiki.org/wiki/Extension:LinkedWiki
 * @license CC-by-sa V4.0
 */

require_once 'coverage.php';

class TestCoverage
{
    public static function start()
    {
        global $wrapper;
        $wrapper = new Coverage\Wrapper();
        $wrapper->dir = '/cover/';
        $wrapper->start();
    }

    public static function stop()
    {
        global $wrapper;
        $wrapper->stop();
    }

    public static function export()
    {
        $reader = new Coverage\Reader('/cover/');
        $filter = new Coverage\Filter();
        $filter->excludeFileRegex = '/\/yii\/.*\/framework\//';
        $merger = new Coverage\Merger();
        $converter = new Coverage\Converter();
        foreach ($reader as $k => $data) {
            $data = $filter->filterData($data);
            $merger->mergeData($data);
        }
        file_put_contents('/cover/clover.xml', $converter->toClover($merger->data));

    }
}
