---
name: markdown-documentation
description: >-
  Use before writing or editing any Markdown file (README, docs, AGENTS.md,
  CLAUDE.md, changelogs, plans). Covers GitHub Flavored Markdown formatting,
  lists, links, tables, code blocks, alerts and collapsible sections. Includes
  the markdownlint-cli2 check on changed files.
paths:
  - "**/*.md"
---

# Markdown Documentation

House rules for Markdown that renders well on GitHub and passes markdownlint.
Syntax details live in the references; load one only when the task needs it.

## Reference Guides

| Topic                                      | File                                                                                                  | Load When                                            |
| ------------------------------------------ | ----------------------------------------------------------------------------------------------------- | ---------------------------------------------------- |
| Alerts and callouts                        | [alerts-and-callouts.md](references/alerts-and-callouts.md)                                           | Note, tip, important, warning, caution blocks        |
| Collapsible sections, highlighting, badges | [collapsible-sections.md](references/collapsible-sections.md)                                         | `<details>`/`<summary>`, syntax highlighting, badges |
| Extended GFM syntax                        | [extended-syntax-github-flavored-markdown.md](references/extended-syntax-github-flavored-markdown.md) | Footnotes, task lists, autolinks, emoji              |
| Links, images, code blocks, tables         | [links-and-images.md](references/links-and-images.md)                                                 | Link and image syntax, fenced code, table syntax     |
| Lists                                      | [lists.md](references/lists.md)                                                                       | Ordered, unordered, nested and task lists            |
| Text formatting                            | [text-formatting.md](references/text-formatting.md)                                                   | Bold, italic, strikethrough, emphasis                |

## Line Length and Wrapping

- Fill prose to 100 columns: wrap each paragraph so lines run as close to 100
  characters as they can, not one sentence per line.
- After an edit to a paragraph, re-fill only that paragraph; leave untouched
  paragraphs alone so the diff stays small.
- Tables are exempt from the limit, and table rows stay on one line. A literal
  path, URL or identifier that cannot be split may run past 100 columns on a line
  of its own, in code blocks as well; other code block lines are not exempt.

## Linting

Run `markdownlint-cli2 <files>` from the repository root on every changed
Markdown file, including agent instruction files (`AGENTS.md`, `CLAUDE.md`,
`SKILL.md`), so that `.markdownlint.yaml` applies, and fix what it reports.

## Code Blocks

- Fence code with backticks, never with tildes or by four-space indentation.
  A fence inside a list item is indented with the item.
- Give every fenced block a language. Use `text` for plain output or
  pseudocode.
- Pick the language that matches the content (`bash`, `python`, `rust`,
  `toml`, `yaml`, `json`, `sql`, `markdown`), because syntax highlighting
  depends on it.

## Tables

- Surround tables with blank lines, and give every row the same number of
  cells.
- Use leading and trailing pipes, and pad cells so that columns align, as oxfmt formats them.

## General Rules

- ATX headings (`#`), with blank lines around headings, lists, code blocks and
  tables.
- One H1 per document, as its first heading.
- Heading levels go down one at a time, and a heading does not end with `.`,
  `,`, `;`, `:`, `!` or `?`.
- Title Case for headings: capitalize every word except articles,
  coordinating conjunctions and prepositions of up to four letters (`a`,
  `the`, `and`, `or`, `of`, `to`, `in`, `with`) that are not first or last.
  Short verbs and pronouns are capitalized (`Is`, `That`), and so is each
  part of a hyphenated word (`Repository-Wide`). Names and code keep their
  own spelling, as in `# base-install`.
- No trailing whitespace except two spaces for a hard line break, no consecutive
  blank lines and a single newline at the end of the file.
- Inline HTML only for `<div>`, `<img>`, `<details>` and `<summary>`; write
  everything else in Markdown.
- Dashes (`-`) for bullets, numbers counting up (`1.`, `2.`, `3.`) for ordered lists and `---` for
  horizontal rules.
- Underscores for emphasis (`_italic_`) and double asterisks for bold (`**bold**`), as oxfmt formats
  them.

## Links, Images and Accessibility

- Use descriptive link text, not "click here".
- Use relative links for files inside the repository.
- Write a URL as a link or in angle brackets (`<https://…>`), never bare.
- Give every image alt text, and do not put text content in images.
