document.addEventListener("DOMContentLoaded", (event) => {

    document.addEventListener("show.bs.collapse", async function (e) {
        if (e.target.getAttribute("data-loaded") === "true") {
            return;
        }
        id = e.target.getAttribute("data-mail-id");
        const bodyDiv = e.target.querySelector(".accordion-body");

        bodyDiv.innerHTML = '<span class="loader"></span>';

        const response = await fetch(`/api/getMail/${id}`)
        if (!response.ok) {
            console.error('Error fetching mail data:', response.statusText);
            return;
        }

        try {
            const json = await response.json();
            gruppierteDaten = gruppiereEintraege(json);
            bodyDiv.innerHTML = formatVorgaenge(gruppierteDaten, id, json.length);
            e.target.setAttribute("data-loaded", "true");
        } catch (error) {
            console.error('Error parsing JSON:', error);
            bodyDiv.innerHTML = `<p>Error loading mail data.</p>`;
        }

    });


});



function gruppiereEintraege(daten) {
    const gruppen = new Map();
    for (const eintrag of daten) {

        const key = `${eintrag.script}|${eintrag.datei}`;
        const datum = parseDatum(eintrag.datum);

        if (!gruppen.has(key)) {
            gruppen.set(key, {
            datum: datum,
            script: eintrag.script,
            datei: eintrag.datei,
            vorgaenge: [],
            geloescht: false,
            });
        }

        const gruppe = gruppen.get(key);
        gruppe.vorgaenge.push({
            script: eintrag.script,
            vorgang: eintrag.vorgang,
            datum: parseDatum(eintrag.datum),
        });

        if (["unlink", "delete"].includes(eintrag.vorgang)) {
            gruppe.geloescht = true;
        }
    }

    return [...gruppen.values()];
}

function parseDatum(str) {
    const [datumTeil, zeitTeil] = str.split(" ");
    const [tag, monat, jahr] = datumTeil.split("/").map(Number);
    const [h, m, s] = zeitTeil.split(":").map(Number);
    return new Date(jahr, monat - 1, tag, h, m, s);
}


function formatVorgaenge(vorgaenge, id, gesamtzahl) {
    let html = '';
    html += '<p>Es wurden <strong>' + vorgaenge.length + '</strong> Dateien erstellt/gelesen/gelöscht/geändert. ';
    html += 'Ingesammt wurden ' + gesamtzahl + ' Dateieoperationen protokolliert.';
    if (gesamtzahl >= 499){
        html += '<div class="alert alert-warning" role="alert">Pro E-Mail werden offensichtlich nicht mehr als 499 Dateioperationen verschickt. Daher können Dateioperationen stattgefunden haben die hier nicht aufgelistet sind.</div>';
    }
    html += 'Dateien die während des Logging Zeitraums sowohl erstellt <code>(create)</code> als auch gelöscht <code>(unlink)</code> wurden, werden mit dem Symbol 🗑️ gekennzeichnet. <button class="btn btn-sm btn-outline-secondary" onclick="toggleDeleted(' + id + ')" id="toggleDeletedBtn">Gelöschte Dateien (🗑️) ausblenden</button></p>';
    html += '</p><p>Die Vorgänge sind nach Datei gruppiert.</p>'
    html += '<table class="table table-striped table-sm" id="vorgaengeTable' + id + '">';
    html += '  <thead>';
    html += '      <tr>';
    html += '          <th>Datum</th>';
    html += '          <th>Datei</th>';
    html += '          <th>Vorgänge</th>';
    html += '          <th>Gelöscht</th>';
    html += '      </tr>';
    html += '  </thead>';
    html += '  <tbody>';



    for (const vorgang of vorgaenge) {
        if (vorgang.geloescht) {
            geloescht = '🗑️';
        }

        let formattedDate;
        try {
            formattedDate = vorgang.datum.toLocaleString('de-DE',{day:"2-digit" ,month:"2-digit", year:"numeric", hour:"2-digit", minute:"2-digit", second:"2-digit"});
        } catch (error) {
            console.error('Error formatting date:', error);
            formattedDate = vorgang.datum.toString(); // Fallback to default string representation
        }


        html += '  <tr>';
        html += `      <td data-label="Datum">${formattedDate}</td>`;
        html += `      <td data-label="Datei">${formatFile(vorgang.datei)}</td>`;
        html += `      <td data-label="Vorgänge">${renderVorgaenge(vorgang.vorgaenge).outerHTML}</td>`;
        html += `      <td data-label="Gelöscht">${vorgang.geloescht ? geloescht : ''}</td>`;
        html += '  </tr>';
    }

    html += '</tbody></table>';
    html += `<p><button class="btn btn-sm btn-success" onclick="mailAuditDone('${id}')"  data-message-id="${id}">📨 E-Mail geprüft, verschiebe die Mail in Ordner ${foldername}</button></p>`;
    return html;

}

function formatFile(file){

    const dateielm = document.createElement("span");
    dateielm.textContent = file;
    text = dateielm.textContent;


    const parts = file.split("/");
    const name = parts.pop();
    const dir = parts.join("/");
    return `${dir}/<strong>${name}</strong>`;
}


function gruppiereVorgaenge(vorgaenge) {
    const zaehler = new Map();

    for (const { vorgang } of vorgaenge) {
        zaehler.set(vorgang, (zaehler.get(vorgang) ?? 0) + 1);
    }

    return [...zaehler]
        .map(([vorgang, anzahl]) => `${vorgang} (${anzahl}x)`)
        .join(", ");
}

const zeitFormat = new Intl.DateTimeFormat("de-DE", {
    dateStyle: "short",
    timeStyle: "medium",
});

function renderVorgaenge(vorgaenge) {
    const zaehler = new Map();

    for (const { vorgang } of vorgaenge) {
        zaehler.set(vorgang, (zaehler.get(vorgang) ?? 0) + 1);
    }

    const zusammenfassung = [...zaehler]
        .map(([vorgang, anzahl]) => `${vorgang} (${anzahl}x)`)
        .join(", ");

    const details = document.createElement("details");

    const summary = document.createElement("summary");
    summary.textContent = zusammenfassung;

    const liste = document.createElement("ul");
    liste.classList.add("list-group");
    liste.classList.add("list-group-flush");
    for (const { script, vorgang, datum } of vorgaenge) {
        liste.append(erstelleVorgangItem({script, vorgang, datum}));
    }

    details.append(summary, liste);
    return details;
}


function erstelleVorgangItem({ vorgang, datum, script }) {
    const li = document.createElement("li");
    li.classList.add("list-group-item")
    li.classList.add("smaller")

    const strong = document.createElement("strong");
    strong.textContent = vorgang;

    const code = document.createElement("code");
    code.textContent = script;

    li.append(
        strong,
        document.createElement("br"),
        `Wann: ${datum.toLocaleString('de-DE',{day:"2-digit" ,month:"2-digit", year:"numeric", hour:"2-digit", minute:"2-digit", second:"2-digit"})}`,
        document.createElement("br"),
        "Script: ",
        code
    );

    return li;
}


function toggleDeleted(id){
    const table = document.getElementById("vorgaengeTable" + id);
    const rows = table.getElementsByTagName("tr");
    toggleDeletedBtn = document.getElementById("toggleDeletedBtn");
    toggleDeletedBtn.innerHTML = toggleDeletedBtn.innerHTML.includes("ausblenden") ? "Gelöschte Dateien (🗑️) einblenden" : "Gelöschte Dateien (🗑️) ausblenden";
    for (let i = 1; i < rows.length; i++) { // Start from 1 to skip the header row
        const cells = rows[i].getElementsByTagName("td");
        if (cells.length > 0) {
            const deletedCell = cells[cells.length - 1]; // Last cell is the "Gelöscht" column
            if (deletedCell.textContent.trim() === "🗑️") {
                rows[i].style.display = rows[i].style.display === "none" ? "" : "none";
            }
        }
    }
}

async function mailAuditDone(id) {
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = metaTag.getAttribute('content');

        const formData = new FormData();
        formData.append("id", id);

        const response = await fetch(`/api/moveMail/`,
            {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                body: formData
            }
        )

        if (!response.ok) {
            console.error('Error moving mail:', response.statusText);
            return;
        }
        const result = await response.json();

        if (result.nextToken && metaTag) {
            metaTag.setAttribute('content', result.nextToken);
        }
        document.querySelector('.accordion-item[data-mail-id="' + id + '"]').remove();


};