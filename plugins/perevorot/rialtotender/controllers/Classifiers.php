<?php namespace Perevorot\Rialtotender\Controllers;

use Backend\Classes\Controller;
use Perevorot\Rialtotender\Models\Classifier;
use RainLab\Translate\Models\Locale AS LocaleModel;

class Classifiers extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function updateMeasure()
    {
        if(post('source') && $data = file_get_contents(post('source')))
        {
            $data = json_decode(json_encode(json_decode($data), JSON_UNESCAPED_UNICODE));
            $classifier = Classifier::instance();
            $locales = LocaleModel::listAvailable();
            $defLocale = LocaleModel::getDefault()->code;

            foreach($locales AS $code => $locale)
            {
                $classifier_measures = trim($classifier->getTranslateAttribute('measure', $code));

                if($classifier_measures)
                {
                    $classifier_measures = $classifier_measures."\n";

                    foreach($data AS $key => $value)
                    {
                        if(stripos($classifier_measures, "$key=") === FALSE)
                        {
                            $classifier_measures .= "$key=".$value->name_ru."|".$value->symbol_ru."\n";
                        }
                    }

                    if($defLocale != $code) {
                        $classifier->setTranslateAttribute('measure', $classifier_measures, $code);
                    } else {
                        $classifier->measure = $classifier_measures;
                    }
                }
                else {

                    $measures = [];

                    foreach($data AS $key => $value) {
                        $measures[$key] = "$key=".$value->name_ru."|".$value->symbol_ru;
                    }

                    if($defLocale == $code) {
                        $classifier->measure = implode("\n", $measures);
                    } else {
                        $classifier->setTranslateAttribute('measure', implode("\n", $measures), $code);
                    }
                }
            }
        }

        $classifier->save();
        return true;
    }
}