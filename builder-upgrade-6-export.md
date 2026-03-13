# Builder Upgrade 6 — Export/Download Saved Builds

## Problem

Saved builds can only be loaded into the preview. Store owners should be able to download the generated code as a `.tsx` file to use in their own projects or share.

## Solution

Add a "Download" button next to each saved build in `BuildLibrary.tsx`. Clicking it creates a Blob with the code content and triggers a browser download as `{label}.tsx`.

## File

`src/components/builder/BuildLibrary.tsx`

## Changes

Add a `handleDownload` function:

```ts
function handleDownload(build: Build) {
  const blob = new Blob([build.code], { type: "text/typescript" });
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = `${build.label.replace(/[^a-zA-Z0-9_-]/g, "_")}.tsx`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
}
```

Add a Download button in each build row, between Load and Delete.

## Also

Add a "Copy" button to the code preview in `FloatingBuilder.tsx` — this already exists but ensure it also exists in the console builder page.

## Verification

1. Save a build → "Download" button appears in the library
2. Click Download → browser downloads a `.tsx` file with the correct content
3. File name matches the build label (sanitized for filesystem)
