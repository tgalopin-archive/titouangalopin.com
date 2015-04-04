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


/**
 * SEO redirect
 */
$app->get('/blog/', function() use ($app) {
    return new \Symfony\Component\HttpFoundation\RedirectResponse('/blog', 301);
});

$app->get('/blog/blog', function() use ($app) {
    return new \Symfony\Component\HttpFoundation\RedirectResponse('/blog', 301);
});

$app->get('/blog/search', function() use ($app) {
    return new \Symfony\Component\HttpFoundation\RedirectResponse('/blog', 301);
});

$app->get('/blog.html', function() use ($app) {
    return new \Symfony\Component\HttpFoundation\RedirectResponse('/blog', 301);
});

$app->get('/blog/user/1', function() use ($app) {
    return new \Symfony\Component\HttpFoundation\RedirectResponse('/', 301);
});

$app->get('/blog/blog/tagged/{tag}', function() use ($app) {
    return new \Symfony\Component\HttpFoundation\RedirectResponse('/blog', 301);
});

$app->get('/blog/articles/2014/05/simhash', function() use ($app) {
    return new \Symfony\Component\HttpFoundation\RedirectResponse('/blog/2014-05-29-simhash', 301);
});

$app->get('/cv.html', function() use ($app) {
    return new \Symfony\Component\HttpFoundation\RedirectResponse('/portfolio', 301);
});

$app->get('/index.html', function() use ($app) {
    return new \Symfony\Component\HttpFoundation\RedirectResponse('/', 301);
});

$app->get('/about', function() use ($app) {
    return new \Symfony\Component\HttpFoundation\RedirectResponse('/', 301);
});

$app->get('/legal-notice', function() use ($app) {
    return new \Symfony\Component\HttpFoundation\RedirectResponse('/legalities', 301);
});

$app->get('/blog/rss', function() use ($app) {
    return new \Symfony\Component\HttpFoundation\RedirectResponse('/flux.rss', 301);
});

/**
 * Error page
 */
$app->error(function (\Exception $exception, $code) use ($app) {

    return $app['twig']->render('error.html.twig', [
        'code' => $code,
        'message' => Response::$statusTexts[$code]
    ]);

});


/*
 * Application
 */

/**
 * Homepage
 */
$app->match('/', function(\Symfony\Component\HttpFoundation\Request $request) use ($app) {

    $messageSent = false;

    if ($request->getMethod() == 'POST') {
        $context  = stream_context_create([ 'http' => [
            'method'  => 'POST',
            'header'  => 'Content-type: application/x-www-form-urlencoded',
            'content' => http_build_query([
                'secret' => '6Lf10gQTAAAAAN3rfblKWD81WOjQn_2qAiYi0L21',
                'response' => $request->get('g-recaptcha-response'),
                'remoteip' => $request->getClientIp(),
            ])
        ]]);

        $result = file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
        $status = json_decode($result, true);

        $success = isset($status['success']) && $status['success'] === true;

        if ($success) {
            $message = \Swift_Message::newInstance()
                ->setSubject('[titouangalopin.com] Contact form : Message from ' . $request->get('name'))
                ->setFrom($request->get('email'), $request->get('name'))
                ->setTo('galopintitouan@gmail.com')
                ->setBody($request->get('message'));

            $app['mailer']->send($message);

            $messageSent = true;
        }
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
 * Legalities
 */
$app->get('/legalities', function() use ($app) {
    return $app['twig']->render('legalities.html.twig');
});


/**
 * Blog - List
 */
$app->get('/blog', function() use ($app) {

    /** @var Repository $repository */
    $repository = $app['repository'];

    return $app['twig']->render('blog_list.html.twig', [
        'articles' => $repository->findList()
    ]);

});


/**
 * Blog - RSS
 */
$app->get('/flux.rss', function() use ($app) {

    /** @var Repository $repository */
    $repository = $app['repository'];

    $feed = new \Suin\RSSWriter\Feed();

    $channel = new \Suin\RSSWriter\Channel();
    $channel
        ->title('Titouan Galopin')
        ->description('A Web and Mobile developer blog')
        ->url('http://www.titouangalopin.com')
        ->language('en')
        ->copyright('CC BY-NC-SA')
        ->appendTo($feed);

    $first = true;

    foreach ($repository->findList() as $article) {
        if ($first) {
            $channel->lastBuildDate((int) $article->date->format('U'));
            $first = false;
        }

        $item = new \Suin\RSSWriter\Item();

        $item
            ->title($article->title)
            ->description($app['converter']->convertToHtml($article->intro))
            ->url('http://www.titouangalopin.com/blog/' . $article->slug)
            ->guid('http://www.titouangalopin.com/blog/' . $article->slug, true)
            ->pubDate((int) $article->date->format('U'));

        foreach ($article->tags as $tag) {
            $item->category($tag);
        }

        $item->appendTo($channel);
    }

    $response = new Response($feed->render(), 200, [ 'Content-type' => 'application/rss+xml' ]);
    $response->setEtag(md5($feed->render()));

    return $response;

});


/**
 * Blog - View
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
