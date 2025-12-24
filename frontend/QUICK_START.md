# Quick Start Guide

## ⚠️ Important: Install Dependencies First!

Before running the dev server, you **must** install dependencies:

```bash
cd frontend
npm install
```

This will install all required packages including `@quasar/app-vite` which provides the `quasar dev` command.

## After Installation

1. **Create environment file:**
```bash
cp .env.example .env
```

2. **Edit `.env` and set your API URL:**
```
API_URL=http://localhost:8000/api
```

3. **Start development server:**
```bash
npm run dev
```

## Troubleshooting

### Error: "Unknown command 'dev'"
- **Solution**: Run `npm install` first. The `@quasar/app-vite` package provides the dev command.

### Error: "Cannot find module"
- **Solution**: Delete `node_modules` and `package-lock.json`, then run `npm install` again.

### Port already in use
- **Solution**: Change the port in `quasar.config.js`:
  ```js
  devServer: {
    port: 9001 // Change to available port
  }
  ```

## Project Structure

```
frontend/
├── src/
│   ├── boot/          # Boot files (axios, router)
│   ├── components/    # Reusable components
│   ├── layouts/       # Layout components
│   ├── pages/         # Page components
│   ├── router/        # Vue Router
│   ├── services/      # API services
│   ├── stores/        # Pinia stores
│   └── main.js        # Entry point
├── quasar.config.js   # Quasar configuration
└── package.json       # Dependencies
```

