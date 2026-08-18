# Commit Rules

## No GPG Signing

Never sign commits with GPG or any other signing method. Always use:
```
git commit -m "message"  # NOT: git commit -S -m "message"
```

Pass `--no-gpg-sign` if git config forces signing:
```
git -c commit.gpgsign=false commit -m "message"
```

## Attribution

Commits have no co-authored trailers or attribution footers.
