#!/usr/bin/env node
// Smoke test: spawns the MCP server over stdio, lists tools/resources/prompts,
// and calls a few read-only tools against the configured Laravel instance.
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { Client } from '@modelcontextprotocol/sdk/client/index.js';
import { StdioClientTransport } from '@modelcontextprotocol/sdk/client/stdio.js';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const strict = process.argv.includes('--strict');

const client = new Client({ name: 'jobs-site-mcp-smoke', version: '1.0.0' });
await client.connect(new StdioClientTransport({
  command: process.execPath,
  args: [path.join(__dirname, 'server.js')],
  env: process.env,
  stderr: 'pipe',
}));

const { tools } = await client.listTools();
const { resources } = await client.listResources();
const { prompts } = await client.listPrompts();
console.log(`tools=${tools.length} resources=${resources.length} prompts=${prompts.length}`);

const checks = [
  ['site_overview', {}],
  ['categories_list', {}],
  ['jobs_search', { per_page: 2 }],
  ['artisan_commands', {}],
  ['db_query', { query: 'select count(*) as c from job_listings' }],
];

let failures = 0;
for (const [name, args] of checks) {
  const res = await client.callTool({ name, arguments: args });
  const text = res.content?.[0]?.text ?? '';
  const ok = !res.isError;
  if (!ok) failures++;
  console.log(`${ok ? 'ok  ' : 'FAIL'} ${name}: ${text.slice(0, 120).replace(/\s+/g, ' ')}`);
}

const prompt = await client.getPrompt({ name: 'publish_scraped_job', arguments: {} });
console.log(`ok   prompt publish_scraped_job (${prompt.messages.length} message)`);

await client.close();
if (strict && failures) {
  console.error(`${failures} tool call(s) failed.`);
  process.exit(1);
}
