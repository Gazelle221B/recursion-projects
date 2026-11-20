<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Restaurant Chain Mockup Extension</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>Restaurant Chain Mockup Extension</h1>
    <form action="download.php" method="post">
        <label for="employeeCount">Employees per location (1-20):</label>
        <input type="number" id="employeeCount" name="employeeCount" min="1" max="20" value="3" required>

        <label for="minSalary">Minimum salary:</label>
        <input type="number" id="minSalary" name="minSalary" min="0" value="25000" required>

        <label for="maxSalary">Maximum salary:</label>
        <input type="number" id="maxSalary" name="maxSalary" min="0" value="90000" required>

        <label for="locationCount">Number of locations (1-10):</label>
        <input type="number" id="locationCount" name="locationCount" min="1" max="10" value="3" required>

        <label for="zipMin">Zip code range (min):</label>
        <input type="number" id="zipMin" name="zipMin" min="0" max="99999" value="10000" required>

        <label for="zipMax">Zip code range (max):</label>
        <input type="number" id="zipMax" name="zipMax" min="0" max="99999" value="99999" required>

        <label for="format">File type:</label>
        <select id="format" name="format">
            <option value="html">HTML</option>
            <option value="json">JSON</option>
            <option value="txt">Text</option>
            <option value="markdown">Markdown</option>
        </select>

        <button type="submit">Generate</button>
    </form>
</body>
</html>
