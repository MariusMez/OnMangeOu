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
