<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name') }}</title>
        <style>
            body { font-family: system-ui, sans-serif; margin: 2rem 1rem; color: #222; }
            form { display: flex; gap: .5rem; align-items: center; flex-wrap: wrap; }
            #status { margin: 1rem 0; }
            #status.error { color: #c00; }
            table { border-collapse: collapse; }
            th, td { border: 1px solid #ccc; padding: .35rem .75rem; text-align: left; }
            th { background: #f3f3f3; }
        </style>
    </head>
    <body>
        <form id="upload-form">
            <input type="file" name="file" accept=".csv,text/csv" required>
            <button type="submit">Upload</button>
        </form>

        <p id="status"></p>

        <table id="result" hidden>
            <thead></thead>
            <tbody></tbody>
        </table>

        <script>
            const form = document.getElementById('upload-form');
            const status = document.getElementById('status');
            const table = document.getElementById('result');

            function showStatus(text, isError = false) {
                status.textContent = text;
                status.className = isError ? 'error' : '';
            }

            function row(cells, tag) {
                const tr = document.createElement('tr');

                cells.forEach((value) => {
                    const cell = document.createElement(tag);
                    cell.textContent = value;
                    tr.append(cell);
                });

                return tr;
            }

            function renderTable(rows) {
                const headers = rows.length ? Object.keys(rows[0]) : [];
                const cells = (r) => Object.values(r).map((value) => value ?? 'NULL');

                table.tHead.replaceChildren(row(headers, 'th'));
                table.tBodies[0].replaceChildren(...rows.map((r) => row(cells(r), 'td')));
                table.hidden = rows.length === 0;
            }

            form.addEventListener('submit', async (event) => {
                event.preventDefault();

                const button = form.querySelector('button');
                button.disabled = true;
                table.hidden = true;
                showStatus('Uploading…');

                try {
                    let response;

                    try {
                        response = await fetch('/api/calculate-common-projects', {
                            method: 'POST',
                            headers: { Accept: 'application/json' },
                            body: new FormData(form),
                        });
                    } catch {
                        showStatus('Could not reach the server.', true);
                        return;
                    }

                    const json = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        showStatus(json.errors?.file?.[0] ?? json.message ?? `Upload failed (${response.status}).`, true);
                        return;
                    }

                    const rows = json.data;

                    if (!Array.isArray(rows) || !rows.every((r) => r !== null && typeof r === 'object')) {
                        console.error('Unexpected response', json);
                        showStatus('Unexpected response from the server: no list of rows in "data".', true);
                        return;
                    }

                    try {
                        renderTable(rows);
                        showStatus(`${rows.length} ${rows.length === 1 ? 'row' : 'rows'}`);
                    } catch (error) {
                        console.error(error, json);
                        showStatus(`Unexpected response from the server: ${error.message}`, true);
                    }
                } finally {
                    button.disabled = false;
                }
            });
        </script>
    </body>
</html>
