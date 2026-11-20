<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Markdown to HTML</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>Markdown to HTML Converter</h1>
    <form action="convert.php" method="post" onsubmit="syncEditor()">
        <div id="editor" style="height: 320px; border: 1px solid #ccc;"></div>
        <textarea id="markdown" name="markdown" hidden></textarea>
        <label for="mode">Output:</label>
        <select id="mode" name="mode">
            <option value="display">Display on page</option>
            <option value="download">Download as file</option>
        </select>
        <button type="submit">Convert</button>
    </form>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.44.0/min/vs/loader.min.js"></script>
    <script>
        let editor;
        require.config({ paths: { vs: "https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.44.0/min/vs" } });
        require(["vs/editor/editor.main"], function () {
            editor = monaco.editor.create(document.getElementById("editor"), {
                value: "# Hello\n\nWrite **Markdown** here and convert it to HTML.",
                language: "markdown"
            });
        });
        function syncEditor() {
            if (editor) {
                document.getElementById("markdown").value = editor.getValue();
            }
        }
    </script>
</body>
</html>
