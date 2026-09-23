#!/usr/bin/env node
// Generates tools.json: the tool manifest shared with the Laravel JSON-RPC
// endpoint (POST /api/mcp/rpc). Run `npm run manifest` after changing tools.
import { writeFileSync } from 'node:fs';
import { z } from 'zod';
import { createServer, TOOL_DEFS } from './server.js';

createServer();

// Each handler receives (args, api). Feed it placeholder args so destructured
// values render as {name} inside captured URL paths, and stub the api client to
// record method/path instead of performing a request.
const proxyArgs = new Proxy({}, {
  get: (_t, prop) => (typeof prop === 'string' ? `{${prop}}` : undefined),
});

const recorder = {
  get: (path) => ({ __call: { method: 'GET', path } }),
  post: (path) => ({ __call: { method: 'POST', path } }),
  put: (path) => ({ __call: { method: 'PUT', path } }),
  del: (path) => ({ __call: { method: 'DELETE', path } }),
};

const tools = [];
for (const { name, meta, handler } of TOOL_DEFS) {
  let http = null;
  try {
    const out = await handler(proxyArgs, recorder);
    if (out && out.__call) {
      // decodeURIComponent restores {placeholder} braces encoded by handlers.
      http = { method: out.__call.method, path: decodeURIComponent(out.__call.path) };
    }
  } catch (err) {
    http = { error: `probe failed: ${err.message}` };
  }
  let inputSchema = { type: 'object', properties: {} };
  try {
    inputSchema = z.toJSONSchema(z.object(meta.inputSchema || {}));
  } catch {
    // keep permissive schema
  }
  delete inputSchema.$schema;
  delete inputSchema.additionalProperties;
  tools.push({
    name,
    title: meta.title || name,
    description: meta.description || meta.title || name,
    inputSchema,
    annotations: meta.annotations || {},
    http,
  });
}

writeFileSync(new URL('./tools.json', import.meta.url), JSON.stringify(tools, null, 2) + '\n');
console.log(`wrote mcp/tools.json: ${tools.length} tools`);
const missing = tools.filter((t) => !t.http?.path).map((t) => t.name);
if (missing.length) console.warn('no http mapping for:', missing.join(', '));
