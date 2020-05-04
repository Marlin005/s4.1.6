# digital-goods-bundle

composer.json
~~~
"autoload": {
    "psr-4": {
        ...
        "Andchir\\DigitalGoodsBundle\\": "vendor/andchir/digital-goods-bundlee/"
    }
},
~~~

~~~
cp /path/to/vendor/andchir/digital-goods-bundle/Resources/config/routes/plugin_digital_goods.yaml \
/path/to/config/routes/plugin_digital_goods.yaml
~~~

/config/bundles.php
~~~
Andchir\DigitalGoodsBundle\DigitalGoodsBundle::class => ['all' => true]
~~~

config/settings.yaml:  
- ``app.pay_after_checkout`` - Start payment after checkout.
- ``app.digilal_goods_send_email`` - Send email with purchases after payment.

## Import products

- 1 - File path
- 2 - Category ID
- 3 - Product ID
~~~
php bin/console app:digital_goods_import "/path/to/data/digital_goods_import/catalogfill.txt" 19 26
~~~

~~~
php bin/console app:digital_goods_import "/path/to/data/digital_goods_import/userorders.txt" 19 27
~~~


