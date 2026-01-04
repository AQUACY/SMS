// API Configuration
// Reads API URL from environment variables
// 
// In development: Reads from .env file (VITE_API_URL)
// In production: Reads from .env.production file or build-time environment variable

// Vite exposes environment variables via import.meta.env
// Variables must be prefixed with VITE_ to be exposed to the client
export const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

