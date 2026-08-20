// @ts-check
import { defineConfig } from 'astro/config';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
  site: 'https://corolive.nz',
  // Cloudflare Pages serves /foo for foo.html, so keep URLs extensionless
  // and un-slashed exactly as the old PHP/static build did.
  trailingSlash: 'never',
  build: { format: 'file' },
  vite: { plugins: [tailwindcss()] },
});
