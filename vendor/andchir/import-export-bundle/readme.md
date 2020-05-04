Import-Export extension bundle for Shopkeeper 4. 

- Shopkeeper4: [https://modx-shopkeeper.ru/](https://modx-shopkeeper.ru/)
- Technical Support Forum: [http://forum.modx-shopkeeper.ru/](http://forum.modx-shopkeeper.ru/)

#Requirements

- Shopkeeper 4.1.2+
- PHP 7.2+
- PHP extensions: mbstring, dom, iconv, gd, xml, zip.
- Ability to use Composer for installation.

# Installation

1.   
    ~~~
    composer require phpoffice/phpspreadsheet
    composer require behat/transliterator
    ~~~

2. 
    composer.json
    ~~~
    "autoload": {
        "psr-4": {
            ...,
            "Andchir\\ImportExportBundle\\": "vendor/andchir/import-export-bundle/"
        }
    }
    ~~~

3.  
    config/bundles.php
    ~~~
    Andchir\ImportExportBundle\ImportExportBundle::class => ['all' => true]
    ~~~

4.  
    config/packages/doctrine_mongodb.yaml
    ~~~
    ImportExportBundle:
        is_bundle: true
        type: annotation
        dir: 'Document'
        prefix: Andchir\ImportExportBundle\Document\
        alias: ImportExportBundle
    ~~~

5.  
    config/resources/admin_menu.yaml
    ~~~
    - { title: 'IMPORT_EXPORT', route: '/module/import-export', icon: 'icon-inbox' }
    ~~~
    or
    ~~~
    - { title: 'IMPORT_EXPORT', route: '/import-export', icon: 'icon-inbox' }
    ~~~

6.  
    ~~~
    cp vendor/andchir/import-export-bundle/Resources/config/routes/plugin_import_export.yaml \
    config/routes/plugin_import_export.yaml
    ~~~

7. 
    ~~~
    composer update
    ~~~

8. 
    For development:
    ~~~
    cp -r vendor/andchir/import-export-bundle/frontend/projects frontend && \
    cp vendor/andchir/import-export-bundle/frontend/angular.json frontend
    ~~~

***

Build for development mode:
~~~
ng build import-export --baseHref="/admin/module/import-export/" \
--deployUrl="/bundles/importexport/admin/bundle-dev/" \
--outputPath="../public/bundles/importexport/admin/bundle-dev" --watch=true
~~~

Build for production:
~~~
ng build import-export --prod --baseHref="/admin/module/import-export/" \
--deployUrl="/bundles/importexport/admin/bundle/" \
--outputPath="../public/bundles/importexport/admin/bundle"
~~~

---

~~~
ln -s /path/to/vendor/andchir/import-export-bundle/frontend/projects/import-export/src/app \
/path/to/frontend/src/app/import-export
~~~

