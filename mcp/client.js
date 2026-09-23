import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

function loadDotEnv() {
  const file = path.join(__dirname, '..', '.env');
  if (!fs.existsSync(file)) return;
  for (const raw of fs.readFileSync(file, 'utf8').split(/\r?\n/)) {
    const line = raw.trim();
    if (!line || line.startsWith('#')) continue;
    const eq = line.indexOf('=');
    if (eq === -1) continue;
    const key = line.slice(0, eq).trim();
    let value = line.slice(eq + 1).trim();
    if ((value.startsWith('"') && value.endsWith('"')) || (value.startsWith("'") && value.endsWith("'"))) {
      value = value.slice(1, -1);
    }
    if (process.env[key] === undefined) process.env[key] = value;
  }
}

loadDotEnv();

export const config = {
  baseUrl: (process.env.JOBS_SITE_URL || process.env.APP_URL || 'http://127.0.0.1:8000').replace(/\/+$/, ''),
  token: process.env.MCP_API_TOKEN || '',
  timeoutMs: parseInt(process.env.JOBS_SITE_MCP_TIMEOUT_MS || '120000', 10),
  httpPort: parseInt(process.env.JOBS_SITE_MCP_PORT || '3040', 10),
  httpToken: process.env.JOBS_SITE_MCP_HTTP_TOKEN || process.env.MCP_API_TOKEN || '',
};

export class ApiError extends Error {
  constructor(message, status, body) {
    super(message);
    this.status = status;
    this.body = body;
  }
}

function buildQuery(params = {}) {
  const qs = new URLSearchParams();
  for (const [key, value] of Object.entries(params)) {
    if (value === undefined || value === null || value === '') continue;
    if (Array.isArray(value)) {
      value.forEach((v) => qs.append(`${key}[]`, String(v)));
    } else if (typeof value === 'boolean') {
      qs.set(key, value ? '1' : '0');
    } else {
      qs.set(key, String(value));
    }
  }
  const s = qs.toString();
  return s ? `?${s}` : '';
}

function stripUndefined(obj) {
  if (!obj || typeof obj !== 'object' || Array.isArray(obj)) return obj;
  return Object.fromEntries(Object.entries(obj).filter(([, v]) => v !== undefined));
}

export async function request(method, endpoint, { query, body, auth = true } = {}) {
  const url = `${config.baseUrl}${endpoint}${buildQuery(query)}`;
  const headers = { Accept: 'application/json' };
  if (auth) {
    if (!config.token) {
      throw new ApiError('MCP_API_TOKEN is not configured for the MCP client.', 0, null);
    }
    headers.Authorization = `Bearer ${config.token}`;
  }
  const init = { method, headers, signal: AbortSignal.timeout(config.timeoutMs) };
  if (body !== undefined) {
    headers['Content-Type'] = 'application/json';
    init.body = JSON.stringify(stripUndefined(body));
  }

  let res;
  try {
    res = await fetch(url, init);
  } catch (err) {
    throw new ApiError(`Request to ${url} failed: ${err.message}`, 0, null);
  }

  const text = await res.text();
  let data;
  try {
    data = text ? JSON.parse(text) : null;
  } catch {
    data = { raw: text.slice(0, 2000) };
  }

  if (!res.ok) {
    const message = (data && (data.message || data.error)) || `HTTP ${res.status}`;
    throw new ApiError(message, res.status, data);
  }
  return data;
}

export const api = {
  get: (endpoint, query, opts) => request('GET', endpoint, { query, ...opts }),
  post: (endpoint, body, opts) => request('POST', endpoint, { body, ...opts }),
  put: (endpoint, body, opts) => request('PUT', endpoint, { body, ...opts }),
  del: (endpoint, body, opts) => request('DELETE', endpoint, { body, ...opts }),
};
