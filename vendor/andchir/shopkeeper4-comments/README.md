# Reviews and ratings for Symfony4

![Comments - screenshot #1](https://github.com/andchir/shopkeeper4-comments/blob/master/Resources/docs/screenshots/screenshot001.png?raw=true "Comments - screenshot #1")

Can be used in Shopkeeper4 and in other applications using Symfony 4. Used DoctrineMongoDBBundle 4+.

## Installing

~~~
composer config extra.symfony.allow-contrib true
~~~
~~~
composer require andchir/shopkeeper4-comments
~~~

Create classes for your entities using ``Andchir\CommentsBundle\Document\CommentAbstract`` and ``Andchir\CommentsBundle\Repository\CommentRepositoryInterface``
or use these examples:
~~~
vendor/andchir/shopkeeper4-comments/Document/Comment.php.dist
vendor/andchir/shopkeeper4-comments/Repository/CommentRepository.php.dist
~~~

Add to your twig template:
~~~
{% block stylesheets -%}
    {{ parent() }}
    <link href="{{ asset('bundles/comments/css/comments.css') }}" rel="stylesheet">
{% endblock -%}
~~~
Your block name may vary.

~~~
{% include '@Comments/Default/async.html.twig' with {'threadId': currentCategory.contentTypeName ~ '_' ~ currentId} only %}
~~~

## Installing component for [Shopkeeper4](https://github.com/andchir/shopkeeper4)

Add to config/resources/admin_menu.yaml
~~~
- { title: 'COMMENTS', route: '/module/comments', icon: 'icon-message-circle' }
~~~

Open "src/App/Repository/CommentRepository.php"
and replace
~~~
class CommentRepository extends DocumentRepository implements CommentRepositoryInterface
~~~
to:
~~~
class CommentRepository extends BaseRepository implements CommentRepositoryInterface
~~~

## Manual installing

composer.json
~~~
"autoload": {
    "psr-4": {
        ...
        "Andchir\\CommentsBundle\\": "vendor/andchir/shopkeeper4-comments/"
    }
},
~~~

config/bundles.php
~~~
Andchir\CommentsBundle\CommentsBundle::class => ['all' => true]
~~~

## Development

Create symlink:
~~~
ln -s /path/to/vendor/andchir/shopkeeper4-comments/frontend/projects/comments \
/path/to/frontend/projects/comments
~~~

Build for development mode:
~~~
ng build comments --baseHref="/admin/module/comments/" \
--deployUrl="/bundles/comments/admin/bundle-dev/" \
--outputPath="../public/bundles/comments/admin/bundle-dev" --watch=true
~~~

Build for production:
~~~
ng build comments --prod --baseHref="/admin/module/comments/" \
--deployUrl="/bundles/comments/admin/bundle/" \
--outputPath="../public/bundles/comments/admin/bundle"
~~~
