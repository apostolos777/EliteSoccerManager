import axios from 'axios';
import * as SecureStore from 'expo-secure-store';

const API_URL = process.env.API_URL || 'http://10.0.2.2:4000';

const api = axios.create({
  baseURL: API_URL,
  timeout: 5000
});

export async function setToken(token: string) {
  if (!token) return;
  await SecureStore.setItemAsync('vivo_token', token);
  api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}

export async function loadToken() {
  const t = await SecureStore.getItemAsync('vivo_token');
  if (t) api.defaults.headers.common['Authorization'] = `Bearer ${t}`;
  return t;
}

export async function clearToken() {
  await SecureStore.deleteItemAsync('vivo_token');
  delete api.defaults.headers.common['Authorization'];
}

export default api;