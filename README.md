##Минимальные требования к PHP и модулям##
* PHP version 7.0 or higher
* PDO PHP Extension
* cURL PHP Extension
* OpenSSL PHP Extension
* Mbstring PHP Library
* ZipArchive PHP Library
* GD PHP Library

##Необходимые программы для работы с зависимостями##
* Composer (getcomposer.org)
* Bower (bower.io)
* npm (nodejs, npmjs.com)

##Разворачивание кода##
```
git clone https://bitbucket.org/perevorot/rialtotender.com.git projectfolder
cd projectfolder
chmod +x init.sh
chmod +x migrate.sh
```

##Установка##
* `./init.sh` инициализация всех зависимостей (npm/bower/composer)
* setup mysql connection and variables `.env` and `integer/*/.env` files
* `./migrate.sh` database migration



##Дополнения к коду после установки##
В случае обновления plugin у octobercms

* Нужно заново внести изминения в файл взяв их из истории коммитов. Причина: баг в самом плагине при смене дефолтного языка.
  ```
    https://bitbucket.org/perevorot/rialtotender.com/commits/9ca040eb4a1a73f2a0395d32d1818a36670c904d
  
    /plugins/rainlab/translate/models/Locale.php
  ```

* Добавить middleware = `web` в файле `/plugins/rainlab/translate/routes.php`

```
Route::group(['middleware' => 'web'], function() use($locale) {
    Route::group(['prefix' => $locale], function () {
        Route::any('{slug}', 'Cms\Classes\CmsController@run')->where('slug', '(.*)?');
    });
    Route::any($locale, 'Cms\Classes\CmsController@run');
});
```

```
Route::group(['middleware' => 'web'], function() use($locale) {
    Route::group(['prefix' => $locale], function() {
        Route::any('{slug}', 'Cms\Classes\CmsController@run')->where('slug', '(.*)?');
    });
});
```

* Добавить в файле plugins/rainlab/translate/controllers/Messages.php :

```
в начало класса
use Perevorot\Rialtotender\Classes\MessagesFromPluginsPartials;
use Perevorot\Rialtotender\Classes\ValidationMessages


function onScanMessages() после ThemeScanner::scan();

Message::importMessages(MessagesFromPluginsPartials::getMessages());
Message::importMessages(ValidationMessages::getMessageCodes());
```
