Vivo Mobile (Expo + React Native)

Quickstart (dev):
- Install dependencies: `pnpm install` from repo root
- Start Expo: `pnpm --filter mobile start`
- Run on Android emulator: `pnpm --filter mobile android`

Notes:
- By default the API base URL is `http://10.0.2.2:4000` which maps to localhost for Android emulators. Set `API_URL` environment variable in your shell or use secure config for real devices.
- The sample Login screen calls `/auth/login` and expects `{ email, password }`.
- Persist auth tokens using `expo-secure-store` or similar.
