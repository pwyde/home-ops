# Extended Syntax (GitHub Flavored Markdown)

GitHub extends CommonMark with footnotes, emoji shortcodes, autolinks, task
lists, and `@user` and `#issue` references. Mentions and issue references
become links in issues, pull requests and comments, but not in files in a
repository.

```markdown
Footnotes:
A sentence with a footnote[^1].

[^1]: This is the footnote.

Emoji:
:smile: :rocket: :heart:
:+1: :-1: :eyes:

Automatic URL linking (angle brackets, because markdownlint flags bare URLs):
<https://github.com>

Task lists in issues:

- [x] #739
- [ ] https://github.com/octo-org/repo/issues/1
- [ ] Add tests

Mentioning users and teams:
@username
@org/team-name

Referencing issues and PRs:
#123
GH-123
username/repo#123
```
