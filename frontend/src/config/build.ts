// Short commit this build came from, so the footer can show what's deployed.
// Cloudflare Pages exposes the SHA as an env var; fall back to git for local
// builds, and to "dev" when neither is available.
import { execSync } from 'node:child_process';

function fromGit(): string | null {
  try {
    const rev = execSync('git rev-parse --short HEAD', {
      encoding: 'utf-8',
      stdio: ['ignore', 'pipe', 'ignore'],
    }).trim();
    const dirty = execSync('git status --porcelain', {
      encoding: 'utf-8',
      stdio: ['ignore', 'pipe', 'ignore'],
    }).trim();
    return dirty ? `${rev}-dirty` : rev;
  } catch {
    return null;
  }
}

const cf = process.env.CF_PAGES_COMMIT_SHA?.slice(0, 7);

export const GIT_REV = cf || fromGit() || 'dev';
