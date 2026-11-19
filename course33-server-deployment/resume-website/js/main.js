// ポートフォリオページで projects.json を読み込み、カードとして描画する
async function loadProjects() {
  const container = document.getElementById("project-list");
  if (!container) return;
  try {
    const response = await fetch("data/projects.json");
    const projects = await response.json();
    container.innerHTML = projects.map(p => `
      <article class="card">
        <h3>${p.title}</h3>
        <p>${p.description}</p>
        <p class="tags">${p.tags}</p>
      </article>`).join("");
  } catch (e) {
    container.innerHTML = "<p>プロジェクトの読み込みに失敗しました。</p>";
  }
}
document.addEventListener("DOMContentLoaded", loadProjects);
