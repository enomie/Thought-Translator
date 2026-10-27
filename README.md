# Thought Translator (gestammel)

Small local web app: dump rough notes, pick a mode, get a polished result via [Groq](https://groq.com).

## Requirements

- PHP 8+ with **cURL**
- Groq API key → [console.groq.com/keys](https://console.groq.com/keys)

## Setup

1. Set your key in `api.php` (`$apiKey = 'gsk_…'`).
2. From this folder:

```bash
php -S localhost:8000
```

3. Open **http://localhost:8000** (do not open `index.html` via `file://` — `fetch` to `api.php` will fail).

## Usage

- **Mode** — what kind of rewrite you want.
- **Output EN / DE** — language of the result (input can be either).
- **Ctrl/Cmd + Enter** — submit.

| Mode | Purpose |
|------|---------|
| Text / paragraph | Clear, natural prose |
| Poetry | Lyric / poem |
| Programming | Specs, pseudocode, technical notes |
| Templates | Document skeletons (ISO, checklists, manuals, app requirements, …) |
| Legal | Structured memo-style notes (not legal advice) |
| Graphic design | Print / web / UI briefs |
| Photo generation | AI image prompts |
| Studio softbox | Product-shot prompts (fixed studio look) |

## Files

- `index.html` — UI
- `api.php` — Groq proxy (model: `openai/gpt-oss-120b`)

Keep API keys out of git if you publish the repo.
