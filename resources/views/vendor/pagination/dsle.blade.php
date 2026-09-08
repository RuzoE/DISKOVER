@if ($paginator->hasPages())
    <nav class="pagination" role="navigation" aria-label="Paginación">
        @if ($paginator->onFirstPage())
            <span class="pagination__link is-disabled" aria-disabled="true">&laquo;</span>
        @else
            <a class="pagination__link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Anterior">&laquo;</a>
        @endif

        <span class="pagination__status">
            Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}
        </span>

        @if ($paginator->hasMorePages())
            <a class="pagination__link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Siguiente">&raquo;</a>
        @else
            <span class="pagination__link is-disabled" aria-disabled="true">&raquo;</span>
        @endif
    </nav>
@endif
