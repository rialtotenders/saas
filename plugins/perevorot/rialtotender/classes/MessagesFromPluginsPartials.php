<?php

namespace Perevorot\Rialtotender\Classes;

use File;

class MessagesFromPluginsPartials
{
    public static function getMessages()
    {
        $partials = [
            './plugins/perevorot/form/components/partials/*.htm',
            './plugins/perevorot/form/components/partials/blocks/*.htm',
            './plugins/perevorot/form/components/partials/_tender_page_blocks/*.htm',
            './plugins/perevorot/form/components/partials/contract_blocks/*.htm',
            './plugins/perevorot/form/components/partials/messages/*.htm',
            './plugins/perevorot/form/components/partials/pages/*.htm',
            './plugins/perevorot/form/components/partials/plan_blocks/*.htm',
            './plugins/perevorot/uploader/components/fileuplaoder/*.htm',
            './plugins/perevorot/users/components/partials/_blocks/*.htm',
            './plugins/perevorot/users/components/partials/_tendercreate_scripts/*.htm',
            './plugins/perevorot/users/components/partials/contractfiles/*.htm',
            './plugins/perevorot/users/components/partials/contracts/*.htm',
            './plugins/perevorot/users/components/partials/messages/*.htm',
            './plugins/perevorot/users/components/partials/plancreate/*.htm',
            './plugins/perevorot/users/components/partials/tendercreate/*.htm',
            './plugins/perevorot/users/components/partials/tenderproject/*.htm',
            './plugins/perevorot/users/components/resetpassword/*.htm',
        ];

        $messages = [];

        foreach ($partials as $path) {

            $files = File::glob($path);

            if(count($files) > 0) {
                foreach($files AS $file) {
                    if ($_file = File::get($file)) {
                        $messages = array_merge($messages, self::processStandardTags($_file));
                    }
                }
            }
        }

        return $messages;
    }

    protected static function processStandardTags($content)
    {
        $messages = [];

        /*
         * Regex used:
         *
         * {{'AJAX framework'|_}}
         * {{\s*'([^'])+'\s*[|]\s*_\s*}}
         *
         * {{'AJAX framework'|_(variables)}}
         * {{\s*'([^'])+'\s*[|]\s*_\s*\([^\)]+\)\s*}}
         */

        $quoteChar = preg_quote("'");

        preg_match_all('#{{\s*'.$quoteChar.'([^'.$quoteChar.']+)'.$quoteChar.'\s*[|]\s*_\s*}}#', $content, $match);
        if (isset($match[1])) {
            $messages = array_merge($messages, $match[1]);
        }

        preg_match_all('#{{\s*'.$quoteChar.'([^'.$quoteChar.']+)'.$quoteChar.'\s*[|]\s*_\s*\([^\)]+\)\s*}}#', $content, $match);
        if (isset($match[1])) {
            $messages = array_merge($messages, $match[1]);
        }

        $quoteChar = preg_quote('"');

        preg_match_all('#{{\s*'.$quoteChar.'([^'.$quoteChar.']+)'.$quoteChar.'\s*[|]\s*_\s*}}#', $content, $match);
        if (isset($match[1])) {
            $messages = array_merge($messages, $match[1]);
        }

        preg_match_all('#{{\s*'.$quoteChar.'([^'.$quoteChar.']+)'.$quoteChar.'\s*[|]\s*_\s*\([^\)]+\)\s*}}#', $content, $match);
        if (isset($match[1])) {
            $messages = array_merge($messages, $match[1]);
        }

        return $messages;
    }
}
