# SMS Frontend

Vue 3 + Quasar frontend for the School Management System.

## 🚀 Getting Started

### Prerequisites
- Node.js 18+
- npm 9+ or yarn 1.21+

### Installation

1. **Install Dependencies**
```bash
npm install
# or
yarn install
```

2. **Configure Environment**
```bash
cp .env.example .env
```

Edit `.env` and set your API URL:
```
API_URL=http://localhost:8000/api
```

3. **Start Development Server**
```bash
npm run dev
# or
quasar dev
```

The app will be available at `http://localhost:9000`

## 📦 Project Structure

```
src/
├── boot/           # Boot files (axios, router, etc.)
├── components/     # Reusable Vue components
├── layouts/        # Layout components
├── pages/          # Page components
├── router/         # Vue Router configuration
├── services/       # API services
├── stores/         # Pinia stores
└── utils/          # Utility functions
```

## 🏗️ Build

### Web
```bash
npm run build
```

### Android
```bash
npm run build:android
```

### PWA
```bash
npm run build:pwa
```

## 📱 Features

- ✅ Vue 3 Composition API
- ✅ Quasar Framework
- ✅ Pinia State Management
- ✅ Axios API Client
- ✅ JWT Authentication
- ✅ Role-based Navigation
- ✅ Responsive Design

## 🔧 Configuration

Edit `quasar.config.js` to customize:
- Build targets
- PWA settings
- Capacitor settings
- And more...

