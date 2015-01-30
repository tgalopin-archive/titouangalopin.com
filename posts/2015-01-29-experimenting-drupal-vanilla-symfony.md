title: Experimenting Drupal and Vanilla integration into Symfony
date: 2015-01-29
tags: [ php, drupal, vanilla, symfony ]
comments: true
published: false
intro: >
    Drupal and Vanilla Forums are two major softwares in PHP. I synchronized them with
    Symfony in the Mana-Drain project. Here is a feedback about it that could help you
    synchronize any external user system with Symfony security component.
---


Symfony is great, flexible and modern. However, it's a framework and
nothing more: it's not a working software like Drupal or Vanilla Forums.

In the context of [Mana-Drain](http://mana-drain.net) (a project I'm working on), we
had to implement a CMS and a forum in Symfony. As this time (2 years ago), there was
not a lot of choices:

- 	do everything ourselves: it's impossible, it's way too much work for us ;
- 	use a Symfony-oriented system, like Symfony CMF and a Symfony forum bundle :
	at this time, Symfony CMF was not finished and there was no stable and maintained
	forum bundle (even today, there is not really such bundle) ;
-	use external systems ;

So basically the only solution available was to use an external system. We had to
synchronize Drupal and Vanilla forums with Symfony.


### A bit of research to understand the Symfony Security component

The Security component of Symfony is in my opinion the most complex one. It uses a lot
of different concepts (voters, handlers, authentication/autorization, ...) and two years
ago, it was not really documented.

So when I first tried to login people in an external service, I had to go into the code
to understand enough things to do what I wanted.

After a couple of hours of research, I was able to react to the component
events using custom `AuthenticationSuccessHandler` and `LogoutSuccessHandler`.


### How can I log in a Drupal user from Symfony?

That's cas the most difficult part. In fact, even now I don't uderstand all the part of it
as I used mysterious piece of code from Drupal to get the things to work.
