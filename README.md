# Melodyx-AJAX

### How to setup
`bundles.php`
```php
<?php

return [
    ...
    Melodyx\Ajax\AjaxBundle::class => ['all' => true]
];
```

`src/Kernel.php`
```php
class Kernel extends BaseKernel
{
    `default kernel content`
    
    private Request $request;

    public function handle(Request $request, int $type = HttpKernelInterface::MAIN_REQUEST, bool $catch = true)
    {
        $this->request = $request;
        return parent::handle($request, $type, $catch);
    }

    protected function getHttpKernel()
    {
        if (!isset($this->request)) {
            return parent::getHttpKernel();
        }

        if ($this->request->get('handle')) {
            return $this->container->get('ajax_kernel');
        }

        return parent::getHttpKernel();
    }
    
    `default kernel content`
}
```

#### Update your template
`base.html.twig` or anywhere else you want to apply ajax
```html
    {{ melodyx_ajax()|raw }} <!-- Add after jquery include -->
```

#### Update your Controller

Create ajax handler:
```php
    public function handle<handler_name>(): void
    {
        $this->addPiece('<any_key_you_want>', '<any string content you send>');
    }
```

`src/Controller/HomepageController.php`
```php
<?php

namespace App\Controller;

use Melodyx\Ajax\AbstractAjaxController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomepageController extends AbstractAjaxController
{
    #[Route('/', name: 'route.homepage')]
    public function index(Request $request): Response
    {
        if ($request->isXmlHttpRequest()) {
            $data = json_decode($request->getContent(), true);
            return new Response(json_encode($data));
        }
        return $this->render('homepage/index.html.twig', [
            'controller_name' => 'HomepageController',
        ]);
    }

    public function handleRedraw(Request $request): void
    {
        $this->addPiece('redraw', 'There is redrawed content');
    }
}
```

#### Now finally use it in template
use `piece` tag
```html
    {% piece <first_parameter_of_method_addPiece> %} {# translate as '<div id="piece-<first_parameter_of_method_addPiece>">'#}
        Dva {# this is default content will be redrawed #}
    {% endpiece %}

<script>
    $(document).ready(function () {
        $('#create').click(function () {
            $.melodyx.ajax({
                url: '{{ melodyx_ajax_handle('<handler_name>') }}',
            })
        });
    });

</script>
```
```html
{% extends 'base.html.twig' %}

{% block title %}Hello HomepageController!{% endblock %}


{% block body %}
    <button id="create">Invoke ajax</button>
    {% piece redraw %}
        There is default text
    {% endpiece %}
{% endblock %}

{% block javascripts %}
    <script>
        $(document).ready(function () {
            $('#create').click(function () {
                $.melodyx.ajax({
                    url: '{{ melodyx_ajax_handle('redraw') }}',
                })
            });
        });

    </script>
{% endblock %}
```

### Enjoy this package