import axios from 'axios';

const API_URL = process.env.API_URL || 'http://10.0.2.2:4000';

const api = axios.create({
  baseURL: API_URL,
  timeout: 5000
});

export default api;