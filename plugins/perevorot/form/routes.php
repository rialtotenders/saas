<?php
    use Cms\Classes\Theme;
    use Perevorot\Form\Classes\ApiOrgsuggest;
    use Perevorot\Rialtotender\Models\Status;
Route::group(['middleware' => 'web'], function() {
    Route::post('form/data/{slug}', function($slug){

        if($slug == 'edrpou')
        {
            $json_object = ApiOrgsuggest::get();
        }
        elseif($slug == 'status')
        {
            $json_object = Status::getStatuses('tender', true, post('lang'));
        }
        elseif($slug == 'cpv')
        {
            $json_object = \Perevorot\Rialtotender\Models\Classifier::getCpv();
        }
        else
        {
            $theme = Theme::getActiveTheme();
            $locale = !empty(post('locale')) ? post('locale') : App::getLocale();
            $theme_path = $theme->getPath();

            $json = $theme_path . '/resources/json/' . $locale . '/' . $slug . '.json';

            if (!file_exists($theme_path . '/resources/json/' . $locale . '/' . $slug . '.json'))
                $json = $theme_path . '/resources/json/ru/' . $slug . '.json';

            $json_object = json_encode((object)[]);

            if (file_exists($json)) {
                $json_object = Cache::rememberForever('form_json_' . $locale . '_' . $slug, function () use ($json) {
                    $data = json_decode(file_get_contents($json));
                    $out = [];

                    foreach ($data as $key => $one) {
                        array_push($out, [
                            'id' => $key,
                            'name' => $one
                        ]);
                    }

                    return $out;
                });
            } else {
                return \Response::make('Page not found', 404);
            }
        }

        return response()->json($json_object);
    });
});