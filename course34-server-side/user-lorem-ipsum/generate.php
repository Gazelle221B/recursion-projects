<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Generate Users</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>Downloadable Lorem Ipsum</h1>
    <form action="download.php" method="post">
        <label for="count">Number of users (1-100):</label>
        <input type="number" id="count" name="count" min="1" max="100" value="5" required>
        <label for="format">Format:</label>
        <select id="format" name="format">
            <option value="html">HTML</option>
            <option value="markdown">Markdown</option>
            <option value="json">JSON</option>
            <option value="txt">Text</option>
        </select>
        <button type="submit">Generate</button>
    </form>
</body>
</html>
