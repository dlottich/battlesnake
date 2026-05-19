# A simple [Battlesnake](http://play.battlesnake.com) written in PHP.

This is a basic implementation of the [Battlesnake API](https://docs.battlesnake.com/snake-api). It's a great starting point for anyone wanting to program their first Battlesnake using PHP. It comes ready to use with [replit.com](https://replit.com) and provides instructions below for getting started. It can also be deployed to [Heroku](https://heroku.com), or any other cloud provider you'd like.

### Technologies

* [PHP7](https://www.php.net/releases/7_0_0.php)
* [Heroku](https://heroku.com) (optional)


### Prerequisites

* [Battlesnake Account](https://play.battlesnake.com)
* [replit.com Account](https://replit.com)
* [GitHub Account](https://github.com/) and [Git Command Line](https://www.atlassian.com/git/tutorials/install-git)  (optional)
* [Heroku Account](https://signup.heroku.com/) and [Heroku Command Line](https://devcenter.heroku.com/categories/command-line)  (optional)

## Running Your Battlesnake on [replit.com](https://replit.com)

[![Run on replit.com](https://replit.com/badge/github/Nettogrof/starter-snake-php)](https://replit.com/github/Nettogrof/starter-snake-php)

1. Login to your [replit.com](https://replit.com) account.

2. Click the 'Run on replit.com' button above, or visit the following URL: https://replit.com/github/Nettogrof/starter-snake-php.

3. You should see your Repl being initialized - this might take a few moments to complete.

4. Once your Repl is ready to run, click `Run ▶️` at the top of the screen.  Once PHP starting process is complete, your Battlesnake server will start and you should see the following:

    ```
    PHP 7.2.24-0ubuntu0.18.04.7 Development Server started at 
    Listening on http://0.0.0.0:8000
    Document root is /home/runner/starter-snake-php
    ```

5. Above the terminal window you'll see the live output from your Battlesnake server, including its URL. That URL will be the URL used to create your Battlesnake in the next step. If you visit that URL in your browser, you should see text similar to this:

    ```
    {"apiversion": "1", "author": "", "color": "#888888", "head": "default", "tail": "default"}
    ```

This means your Battlesnake is running correctly on replit.com.

**At this point your Battlesnake is live and ready to enter games!**



## Customizing Your Battlesnake

Now you're ready to start customizing your Battlesnake's appearance and behavior.

### Changing Appearance

Locate the 'index' section inside [index.php](index.php#L16). You should see code that looks like this:
```php
    $apiversion = "1";
    $author     = "";           // TODO: Your Battlesnake Username
    $color      = "#888888";    // TODO: Personalize
    $head       = "default";    // TODO: Personalize
    $tail       = "default";    // TODO: Personalize

    indexResponse($apiversion,$author,$color,$head, $tail);
```

This function is called by the game engine periodically to make sure your Battlesnake is healthy, responding correctly, and to determine how your Battlesnake will appear on the game board. See [Battlesnake Personalization](https://docs.battlesnake.com/references/personalization) for how to customize your Battlesnake's appearance using these values.

Whenever you update these values, you can refresh your Battlesnake on [your profile page](https://play.battlesnake.com/me/) to use your latest configuration. Your changes should be reflected in the UI, as well as any new games created.

### Changing Behavior

On every turn of each game your Battlesnake receives information about the game board and must decide its next move.

Locate the `move` section inside [index.php](index.php#L34). You should see code that looks like this:
```php
 // read the incoming request body stream and decode the JSON
    $data = json_decode(file_get_contents('php://input'));

    error_log('Received move data: '.print_r($data, true));

    // TODO - Implement your Battlesnake here!
    $possibleMove = ['up', 'down', 'left', 'right'];
    moveResponse($possibleMove[array_rand($possibleMove)]);
```

Possible moves are "up", "down", "left", or "right". To start your Battlesnake will choose a move randomly. Your goal as a developer is to read information sent to you about the board (available in the `$data` variable) and make an intelligent decision about where your Battlesnake should move next. 

See the [Battlesnake Rules](https://docs.battlesnake.com/rules) for more information on playing the game, moving around the board, and improving your algorithm.

### Updating Your Battlesnake

After making changes to your Battlesnake, you can restart your Repl to have the change take effect (or in many cases your Repl will restart automatically).

Once the Repl has restarted you can [create a new game](https://play.battlesnake.com/account/games/create/) with your Battlesnake to watch your latest changes in action.

**At this point you should feel comfortable making changes to your code and starting new Battlesnake games to test those changes!**




## Developing Your Battlesnake Further

Now you have everything you need to start making your Battlesnake super smart! Here are a few more helpful tips:

* Keeping your Repl open in a second window while games are running is helpful for watching server activity and debugging any problems with your Battlesnake.

* The projected is configured with the error_log that you can use the code [error_log](https://www.php.net/manual/en/function.error-log.php) to output information to your server logs. This is very useful for debugging logic in your code during Battlesnake games.

* Review the [Battlesnake API Docs](https://docs.battlesnake.com/snake-api) to learn what information is provided with each command.

* When viewing a Battlesnake game you can pause playback and step forward/backward one frame at a time. If you review your logs at the same time, you can see what decision your Battlesnake made on each turn.



## Joining a Battlesnake Arena

Once you've made your Battlesnake behave and survive on its own, you can enter it into the [Global Battlesnake Arena](https://play.battlesnake.com/arena/global) to see how it performs against other Battlesnakes worldwide.

Arenas will regularly create new games and rank Battlesnakes based on their results. They're a good way to get regular feedback on how well your Battlesnake is performing, and a fun way to track your progress as you develop your algorithm.


## (Optional) Deploying Your First Battlesnake with Heroku

1. [Fork this repo](https://github.com/Nettogrof/starter-snake-php/fork) into your GitHub Account.

2. [Clone your forked repo](https://help.github.com/en/github/creating-cloning-and-archiving-repositories/cloning-a-repository) into your local environment.
    ```shell
    git clone git@github.com:[YOUR-GITHUB-USERNAME]/starter-snake-php.git
    ```

3. [Create a new Heroku app](https://devcenter.heroku.com/articles/creating-apps) to run your Battlesnake.
    ```shell
    heroku create [YOUR-APP-NAME]
    ```

4. [Deploy your Battlesnake code to Heroku](https://devcenter.heroku.com/articles/git#deploying-code).
    ```shell
    git push heroku master
    ```

5. Open your new Heroku app in your browser.
    ```shell
    heroku open
    ```
    If everything was successful, you should see the following text:
    ```
    {"tailType":"default","color":"#888888","headType":"default","author":"","apiversion":"1"}
    ```

6. Optionally, you can view your server logs using the [Heroku logs command](https://devcenter.heroku.com/articles/logging#log-retrieval) `heroku logs --tail`. The `--tail` option will show a live feed of your logs in real-time.

**At this point your Battlesnake is live and ready to enter games!**

### (Optional) Updating Your Battlesnake with Heroku

After making changes, commit them using git and deploy your changes to Heroku.
```shell
git add .
git commit -m "update my battlesnake's appearance"
git push heroku master
```

Once Heroku has updated you can [create a new game](https://play.battlesnake.com/account/games/create/) with your Battlesnake to view your latest changes in action.

**At this point you should feel comfortable making changes to your code and deploying those changes to Heroku!**

## (Optional) Using a Cloud Provider

As your Battlesnake gets more complex, it might make sense to move it to a dedicated hosting provider such as [AWS](https://aws.amazon.com/what-is-cloud-computing) or GCP. We suggest choosing a platform you're familiar with, or one you'd be interested in learning more about.

If you have questions or ideas, our developer community on [Slack](https://play.battlesnake.com/slack) and [Discord](https://play.battlesnake.com/discord) will be able to help out.


## (Optional) Running Your Battlesnake Locally

Eventually you might want to run your Battlesnake server locally for faster testing and debugging. You can do this by installing  [PHP7](https://www.php.net/releases/7_0_0.php), then:

1. [Fork this repo](https://github.com/Nettogrof/starter-snake-php/fork).

1. Clone repo to your development environment:
    ```
    git clone git@github.com:<your github username>/starter-snake-php.git
    ```
1. Start the [PHP built-in web server](https://www.php.net/manual/en/features.commandline.webserver.php):
    ```
    php -S localhost:8080
    ```
1. Test your snake by sending a POST request with a JSON body to it with cURL
    ```
    curl -XPOST -H 'Content-Type: application/json' -d '{"hello": "world"}' http://localhost:8080/start
    ```

This will start the Battlesnake server on port 8080.


**Note:** You cannot create games on [play.battlesnake.com](https://play.battlesnake.com) using a locally running Battlesnake unless you install and use a port forwarding tool like [ngrok](https://ngrok.com/).


### Questions?

All documentation is available at [docs.battlesnake.com](https://docs.battlesnake.com), including detailed Guides, API References, and Tips.

You can also join the Battlesnake Developer Community on [Discord](https://play.battlesnake.com/discord). We have a growing community of Battlesnake developers of all skill levels wanting to help everyone succeed and have fun with Battlesnake :)

