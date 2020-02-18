# LiteCore Contribution Guidelines

### Issues

- **Follow the template** and provide the information we ask for.
- Use Russian properly, or ask someone to examine your words.
- When posting a feature request, try to describe as much as you can, and don't make it too broad.
- Avoid generic titles like "Crash", "Help" or "Broken". Do your best as long as it fits in the line.
- Avoid unneeded emoji reactions, unless you're participating and providing informations.

### Code Contributions

- **Avoid using GitHub Web Editor.** GitHub Web doesn't provide every Git feature, and using the web editor means you haven't tested the code. It's immediately obvious if you've used the Web editor, and if you do, your PR is likely to be rejected. Also **do not use a mobile device** for code editing.
- **No copy-pasted contents.** Not only license issues exist, you're also ignoring what the author intended to do. Blindly copied content are strongly discouraged. (We recommend the original author to open a PR)
- Test your changes before opening a pull request. **Do not submit a PR if the CI fails.**
- Make sure you can fully explain WHY and HOW your changes work. If you can't provide a full and comprehensive explanation as to why your changes work and what the effects are, do not submit a pull request.
- One change per commit. Squash redundant commits.
- Pull requests doing little things like bumping protocol numbers will be closed as spam. We don't need people spamming us with protocol numbers, especially when said people do not TEST things properly before making a PR. There is always a reason why the protocol VERSION NUMBER is changed - to reflect internal BACKWARDS-INCOMPATIBLE changes.
