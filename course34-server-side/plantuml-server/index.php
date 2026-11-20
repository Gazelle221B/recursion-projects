<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PlantUML Server</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>PlantUML Server</h1>
    <div class="layout">
        <div class="editor-pane">
            <textarea id="uml" rows="18">@startuml
actor User
User --> (Login)
User --> (View Diagram)
@enduml</textarea>
            <div class="controls">
                <label for="format">Format:</label>
                <select id="format">
                    <option value="svg">SVG</option>
                    <option value="png">PNG</option>
                    <option value="txt">ASCII (txt)</option>
                </select>
                <button type="button" onclick="downloadDiagram()">Download</button>
                <a href="practice.php">Practice prompts</a>
            </div>
            <h2>Cheat sheet</h2>
            <ul class="cheatsheet">
                <li>Use case: <code>actor User</code>, <code>User --&gt; (Use Case)</code></li>
                <li>Class: <code>class Car { +start() }</code>, composition: <code>Car *-- Engine</code></li>
                <li>Activity: <code>start</code>, <code>:action;</code>, <code>if (...) then ... else ... endif</code>, <code>stop</code></li>
                <li>Sequence: <code>alice -&gt; bob : message</code>, <code>bob --&gt; alice : reply</code></li>
                <li>State: <code>[*] --&gt; State1</code>, <code>State1 --&gt; State2</code></li>
                <li>Mind map: <code>@startmindmap</code> with <code>* root</code>, <code>** child</code></li>
                <li>Gantt: <code>@startgantt</code> with <code>[Task] lasts 3 days</code></li>
            </ul>
        </div>
        <div class="preview-pane">
            <h2>Preview</h2>
            <div id="preview"></div>
        </div>
    </div>

    <script>
        const umlInput = document.getElementById("uml");
        const formatSelect = document.getElementById("format");
        const preview = document.getElementById("preview");
        let timer = null;

        function renderUrl(download) {
            const format = formatSelect.value;
            let url = "render.php?format=" + encodeURIComponent(format) + "&uml=" + encodeURIComponent(umlInput.value);
            if (download) url += "&download=1";
            return url;
        }

        function updatePreview() {
            const format = formatSelect.value;
            if (format === "txt") {
                fetch(renderUrl(false)).then(r => r.text()).then(t => {
                    preview.innerHTML = "";
                    const pre = document.createElement("pre");
                    pre.textContent = t;
                    preview.appendChild(pre);
                });
            } else {
                preview.innerHTML = "";
                const img = document.createElement("img");
                img.src = renderUrl(false);
                img.alt = "PlantUML diagram";
                preview.appendChild(img);
            }
        }

        function downloadDiagram() {
            window.location.href = renderUrl(true);
        }

        umlInput.addEventListener("input", function () {
            clearTimeout(timer);
            timer = setTimeout(updatePreview, 600);
        });
        formatSelect.addEventListener("change", updatePreview);
        updatePreview();
    </script>
</body>
</html>
