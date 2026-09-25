# Druga Perspektiva – Igre (Riječ dana)

A daily Bosnian Wordle for drugaperspektiva.org, packaged as a WordPress plugin.
It runs on your existing WordPress hosting, so nothing else needs to be hosted.

## Features

- **One word per day, the same for everyone.** It changes at midnight in the site's time zone (Settings → General, set it to Sarajevo).
- **Bosnian alphabet.** LJ, NJ and DŽ are single letters with their own keys, and Č, Ć, Đ, Š and Ž are on the keyboard.
  Typing L+J, N+J or D+Ž on a physical keyboard joins them into one letter automatically.
- **Guesses are checked against about 47,000 Bosnian/Croatian/Serbian words.** You can add missing words in the admin panel.
- **Google sign-in, school accounts only.** Only `@2gimnazija.edu.ba` can sign in, and the server checks this.
  The leaderboard name comes from the email (`amina.hodzic` → "Amina Hodzic"), and an admin can fix it to "Amina Hodžić".
- **Anonymous play.**
  - Anyone can play without signing in. Their stats and streak are kept only in their browser, and they don't appear on leaderboards.
  - Signed-in students can tick "Sakrij moje ime na ljestvici" to show up as "Anonimni igrač".
  - If someone plays as a guest and then signs in, today's game carries over to their account.
- **Streaks.** A 🔥 badge shows the current streak, and the stats window shows current and longest streak. A missed day or a lost game resets the streak.
- **Leaderboards:**
  - **Danas:** fewest guesses, then whoever solved it first.
  - **Niz:** longest current streak.
  - **Ovaj mjesec** and **Ukupno:** points (6 for a first-try win … 1 for a sixth-try win).
- **Fair play.** The answer never reaches the browser until the game is over, and a signed-in player gets one game per day.
- **Sharing.** A share button copies an emoji grid (🟩🟨⬛) that students can paste into chats.

## Control panel (WP admin → "Riječ dana")

- **Raspored riječi.** Pick a date and set a special word for it, with an optional message shown after the game ("Sretan Dan škole!"). The table shows past and upcoming days and how many played and solved each day.
  - Words for normal days are picked at random from the word list on the day itself, without repeats.
  - A day's word can't be changed once someone has started playing it.
- **Liste riječi.** Edit the list of possible daily words (225 common words to start with), and add extra words that should count as valid guesses.
- **Igrači.** Rename players (to add č/ć/š/ž), hide someone from the leaderboard, reset today's game, or delete a player.
- **Postavke.** Google Client ID, allowed domain, game title, and the date of game #1.

## Installation

1. Download `dp-igre.zip` from this repository.
2. WordPress admin → **Plugins → Add New → Upload Plugin**, choose the zip, then **Activate**.
3. Create a page (for example "Igre") and put this shortcode in it: `[dp_wordle]`
4. **Settings → General → Timezone:** choose **Sarajevo**.
5. Set up Google sign-in. Until you do, the game works but anonymous play only.
   1. Go to <https://console.cloud.google.com/apis/credentials>. It's best to use a school Google account.
   2. **OAuth consent screen:** choose type **Internal** if you're using a school account, so only school accounts can use it. Set the app name to "Druga Perspektiva".
   3. Go to **Create credentials → OAuth client ID → Web application**.
   4. Under **Authorized JavaScript origins**, add `https://www.drugaperspektiva.org` and `https://drugaperspektiva.org`.
   5. Copy the Client ID into **Riječ dana → Postavke**.

## Notes

- **Caching:** if the site uses a caching plugin (LiteSpeed Cache, WP Rocket, etc.), you normally don't need to change anything. All game data comes from `/wp-json/dpig/v1/…`, which is sent with no-cache headers, and the page itself contains no personal data.
- **Server requirements:** the server must be able to reach `oauth2.googleapis.com`, which is used to verify Google sign-ins. Almost all shared hosting can.
- **Building the zip:** run `./build.sh` in the repository root to rebuild it after changes.
- **Word data:** the list of accepted guesses comes from [FrequencyWords](https://github.com/hermitdave/FrequencyWords) (OpenSubtitles, CC-BY-SA 4.0), filtered to 5-letter words.
