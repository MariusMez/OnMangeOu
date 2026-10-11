import '@fontsource/manrope/400.css';
import '@fontsource/manrope/500.css';
import '@fontsource/manrope/600.css';
import '@fontsource/manrope/700.css';
import '@fontsource/manrope/800.css';
import '@fontsource/noto-serif/400.css';
import '@fontsource/noto-serif/400-italic.css';
import '@fontsource/noto-serif/700.css';
import '@fontsource/noto-serif/700-italic.css';
import './echo';

/**
 * Page de résultat : rechargement dès que le statut de l'analyse change
 * (événement AnalysisUpdated diffusé par Reverb). Un rafraîchissement
 * périodique sert de filet de sécurité si le WebSocket n'est pas disponible.
 */
const page = document.querySelector('[data-analysis-id][data-analysis-running="1"]');

if (page) {
    const id = page.dataset.analysisId;
    const reload = () => window.location.reload();
    const fallback = setTimeout(reload, 12000);

    window.Echo.channel(`analyses.${id}`).listen('AnalysisUpdated', () => {
        clearTimeout(fallback);
        reload();
    });
}

/**
 * Écran de scan : le viseur sert de zone de dépôt, les boutons secondaires
 * ouvrent le sélecteur de photos ou mettent le champ « site web » en avant.
 */
const fileInput = document.querySelector('[data-file-input]');
const fileList = document.querySelector('[data-file-list]');

if (fileInput && fileList) {
    fileInput.addEventListener('change', () => {
        const names = Array.from(fileInput.files).map((file) => file.name);
        fileList.textContent = names.length > 0 ? names.join(' · ') : '';
        fileList.hidden = names.length === 0;
    });

    document.querySelector('[data-pick-photos]')?.addEventListener('click', () => fileInput.click());
}

document.querySelector('[data-focus-website]')?.addEventListener('click', () => {
    const field = document.querySelector('[data-website-input]');
    field?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    field?.focus();
});
