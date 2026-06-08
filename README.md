# Battlesnake PHP Starter

A minimal [Battlesnake](https://play.battlesnake.com) server in PHP, following the [quickstart](https://docs.battlesnake.com/quickstart) and [API webhooks](https://docs.battlesnake.com/api/webhooks).

## Requirements

- PHP 8.1+ (with JSON extension, enabled by default)

## Run locally

```bash
php -S 0.0.0.0:8000 index.php
```

Open [http://localhost:8000/](http://localhost:8000/) — you should see JSON like:

```json
{"apiversion":"1","author":"","color":"#888888","head":"default","tail":"default"}
```

## Expose with ngrok

Battlesnake needs a public URL to reach your local server. [ngrok](https://ngrok.com) tunnels port 8000 to the internet.

### 1. Create an ngrok account

1. Sign up at [ngrok.com](https://ngrok.com) (the free tier is enough).
2. In the dashboard, copy your **authtoken**.

### 2. Install ngrok

**winget (Windows):**

```powershell
winget install ngrok.ngrok
```

Or download from [ngrok.com/download](https://ngrok.com/download), unzip, and add `ngrok.exe` to your PATH.

### 3. Add your authtoken (one time)

```powershell
ngrok config add-authtoken YOUR_AUTHTOKEN_HERE
```

### 4. Start the tunnel

With the PHP server already running on port 8000, open a second terminal:

```powershell
ngrok http 8000
```

ngrok prints a forwarding URL, for example:

```
Forwarding  https://abc123.ngrok-free.app -> http://localhost:8000
```

Copy the **https** URL (no trailing path).

### 5. Register on Battlesnake

1. Go to [play.battlesnake.com/account/battlesnakes](https://play.battlesnake.com/account/battlesnakes).
2. Create a new Battlesnake and paste the ngrok URL into the **URL** field.
3. Save and click **Ping** — your snake should show as operational.

Keep both terminals open (PHP server and ngrok) while playing. The free ngrok URL changes each time you restart ngrok unless you use a reserved domain on a paid plan.

## API endpoints

| Method | Path    | Purpose                          |
|--------|---------|----------------------------------|
| GET    | `/`     | Battlesnake info & customization |
| POST   | `/start`| Game started (response ignored)  |
| POST   | `/move` | Return next move each turn       |
| POST   | `/end`  | Game over (response ignored)     |

## Customize

Edit `Battlesnake::info()` in `Battlesnake.php` for `author`, `color`, `head`, and `tail`.

Improve survival in `Battlesnake::move()` — the starter avoids moving backward and picks a random safe direction. See the TODO comments for bounds, collisions, and food.

## Deploy

Host this project on any PHP-capable platform (Railway, Render, Fly.io, etc.). Point the web root at `index.php` or run the built-in server command above with `PORT` set by your host.

## Project layout

- `index.php` — HTTP routing for Battlesnake webhooks
- `Battlesnake.php` — game logic (`info`, `start`, `move`, `end`)
