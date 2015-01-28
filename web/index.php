<?php

/*
 * Boot
 */

require __DIR__.'/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

$config = require __DIR__ . '/../parameters.php';

$app = new Silex\Application();
$app['debug'] = false;

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../views',
));

$app->register(new Silex\Provider\SwiftmailerServiceProvider());

$app['swiftmailer.options'] = $config['mailer'];

// Services
$app['converter'] = function() {
    return new \League\CommonMark\CommonMarkConverter();
};

$app['formatter'] = function() use($app) {
    return new Formatter($app['converter']);
};

$app['repository'] = function() use($app) {
    return new Repository(__DIR__ . '/../posts', $app['formatter']);
};

$app['paginator'] = function() {
    return new \Knp\Component\Pager\Paginator();
};

$app['paginator_renderer'] = function() use ($app) {
    return function($data) use ($app) {
        return $app['twig']->render('_pagination.html.twig', $data);
    };
};


/*
 * Application
 */

/**
 * Error page
 */
$app->error(function (\Exception $exception, $code) use ($app) {

    $errorMail = <<<EOT
Error "%s %s" occured on page "%s" requested by %s.

Request:
    IP: %s
    Method: %s
    Request URI: %s

Exception:
    Name: %s
    Message: %s
    File: %s
    Line: %s

Trace:

%s
EOT;

    /** @var Request $request */
    $request = $app['request'];

    $errorMail = sprintf(
        $errorMail, $code, Response::$statusTexts[$code], $request->getRequestUri(), $request->getClientIp(),
        $request->getClientIp(), $request->getMethod(), $request->getRequestUri(),
        get_class($exception), $exception->getMessage(), $exception->getFile(), $exception->getLine(),
        $exception->getTraceAsString()
    );

    $message = \Swift_Message::newInstance()
        ->setSubject('[titouangalopin.com] Error ' . $code)
        ->setFrom('contact@titouangalopin.com', 'titouangalopin.com')
        ->setTo('galopintitouan@gmail.com')
        ->setBody($errorMail, 'text/plain');

    $app['mailer']->send($message);

    return $app['twig']->render('error.html.twig', [
        'code' => $code,
        'message' => Response::$statusTexts[$code]
    ]);

});

/**
 * Homepage
 */
$app->match('/', function(\Symfony\Component\HttpFoundation\Request $request) use ($app) {

    $messageSent = false;

    if ($request->getMethod() == 'POST') {
        $message = \Swift_Message::newInstance()
            ->setSubject('[titouangalopin.com] Contact form : Message from ' . $request->get('name'))
            ->setFrom($request->get('email'), $request->get('name'))
            ->setTo('galopintitouan@gmail.com')
            ->setBody($request->get('message'));

        $app['mailer']->send($message);

        $messageSent = true;
    }

    return $app['twig']->render('index.html.twig', [
        'messageSent' => $messageSent,
    ]);

})
->method('GET|POST');


/**
 * Curiculum Vitaea
 */
$app->get('/portfolio', function() use ($app) {
    return $app['twig']->render('portfolio.html.twig');
});


/**
 * Blog - List
 */
$app->get('/blog', function() use ($app) {

    /** @var Repository $repository */
    $repository = $app['repository'];

    // $pagination = $app['paginator']->paginate($repository->findList(), 1, 20);
    // $pagination->renderer = $app['paginator_renderer'];

    return $app['twig']->render('blog_list.html.twig', [
        'articles' => $repository->findList() // $pagination->getItems(),
        //'pagination' => $pagination
    ]);

});


/**
 * Blog - List
 */
$app->get('/blog/{slug}', function($slug) use ($app) {

    /** @var Repository $repository */
    $repository = $app['repository'];

    $article = $repository->findOne($slug);

    if (! $article || ! $article instanceof Article || ! $article->published) {
        throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }

    return $app['twig']->render('blog_view.html.twig', [
        'article' => $article
    ]);

});




/*
 * Terminate
 */

$app->run();
