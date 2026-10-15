# apps/mobile — Ticko customer app

React Native + **Expo SDK 56** app for customers: open tickets with photos, follow the
conversation with the team in real time, get push notifications.

> 🇧🇷 Em português: [README.pt-BR.md](README.pt-BR.md)

Stack: Expo Router (file-based routes in `src/app`), TypeScript strict, TanStack Query,
React Hook Form + Zod, i18next (pt-BR / English), Geist, light and dark themes with the web
app's tokens. API types come from `@ticko/api-client`.

```
src/app/         routes (sign-in, (app)/(tabs)/index + profile, (app)/tickets/new + [id])
src/features/    screens: auth, tickets, profile
src/lib/         config, session (secure store), api, realtime, push, images, deep links
src/components/  UI primitives and empty/loading/error/offline states
src/test/        Jest setup, fake API, fixtures
```

## Run it on your phone (Expo Go)

You need: the Docker stack, Node 22, and a phone with **Expo Go** (SDK 56) on the **same
Wi-Fi** as your computer.

```bash
make up-lan       # the stack, reachable from the LAN (only the web port is exposed)
make demo         # once: demo tenant "acme" with a customer and tickets
make mobile-dev   # writes apps/mobile/.env with your LAN address, starts Expo
```

Scan the QR code with Expo Go (Android) or the Camera app (iOS) and tap **Sign in as the demo
customer** (only offered when the API has `TICKO_DEMO_LOGIN=true`; the app ships no password).

What `make up-lan` changes: it binds Caddy (`WEB_PORT`, e.g. 8090) to `0.0.0.0` instead of
`127.0.0.1`, so devices on your network can reach the web app and API. Postgres, Redis and
Reverb stay on internal Docker networks. Run `make up` to go back to localhost only. If the
phone cannot connect, allow the port in your firewall (e.g. `sudo ufw allow 8090/tcp`) and
check that the Wi-Fi does not isolate clients.

`apps/mobile/.env` (git-ignored, see [.env.example](.env.example)):

| Variable | |
| --- | --- |
| `EXPO_PUBLIC_API_URL` | `http://<your LAN IP>:<WEB_PORT>` or an HTTPS tunnel. Plain HTTP is accepted only in development and only for private addresses; release builds require HTTPS. |
| `EXPO_PUBLIC_TENANT` | Tenant slug used to sign in (`acme` for the demo). |
| `EAS_PROJECT_ID` | Optional; needed for push in a development build. |

`EXPO_PUBLIC_*` values are compiled into the app: **never put a secret there**.

### Without a shared Wi-Fi: tunnel

Expose the API over HTTPS with a tunnel and point the app at it, then let Expo tunnel Metro:

```bash
cloudflared tunnel --url http://localhost:8090      # prints https://<random>.trycloudflare.com
# apps/mobile/.env: EXPO_PUBLIC_API_URL=https://<random>.trycloudflare.com
corepack pnpm --filter @ticko/mobile start --tunnel
```

The tunnel URL is public while it runs: use demo data and stop it when you are done.

### Try realtime

Open a ticket on the phone, then answer it as the agent in the web app
(http://localhost:8090, "Agent" demo button): the reply and the status timeline update on the
phone without refreshing.

### Push notifications

Since SDK 53, **Expo Go cannot receive remote push notifications on Android** (and Expo
recommends development builds on iOS too); the profile screen says so. The server side is
complete and tested. To receive pushes on a phone, use a development build:

1. Create an Expo project (`npx eas init`) and put its id in `EAS_PROJECT_ID`.
2. Android: configure FCM credentials for the project in EAS (`eas credentials`).
3. `npx eas build --profile development --platform android` (or `npx expo run:android`), install it.
4. Sign in, allow notifications. Replies and status changes from the team now arrive as
   "New reply on ticket #XXXXXX" — never with ticket content.

Optional: enable "enhanced push security" in EAS and set `EXPO_ACCESS_TOKEN` in the **root**
`.env` (server side only).

## Security notes

- The API token lives only in `expo-secure-store` (Keychain / Android Keystore, this device
  only) and memory — never AsyncStorage (the lint rule forbids it). It expires (30 days by
  default) and is revoked on the server at sign-out, which also unregisters push for the phone.
- A 401 from the API signs the app out.
- Deep links (`ticko://tickets/<id>`) and notification payloads accept only well-formed ticket
  ids; the screen loads the ticket through the API, which checks access again.
- Photos are re-encoded as JPEG on the phone (drops EXIF/GPS, max 2048 px) and again by the API.
- No `console` output (lint), so nothing personal ends up in device logs.

## Commands

| | |
| --- | --- |
| `make mobile-dev` | Expo dev server (creates `.env` the first time) |
| `make mobile-test` | ESLint, Prettier, `tsc`, Jest with coverage |
| `corepack pnpm --filter @ticko/mobile test` | Jest only |
