@if ($paginator->hasPages())
    <div class="pagination-wrapper">


        <div class="pagination-container">
            <ul class="pagination">
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link">
                            <i class="ri-arrow-left-s-line"></i>
                            <span>Previous</span>
                        </span>
                    </li>
                @else
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">
                            <i class="ri-arrow-left-s-line"></i>
                            <span>Previous</span>
                        </a>
                    </li>
                @endif

                {{-- Pagination Elements --}}
                @foreach ($elements as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <li class="page-item disabled" aria-disabled="true">
                            <span class="page-link dots">{{ $element }}</span>
                        </li>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <li class="page-item active" aria-current="page">
                                    <span class="page-link">{{ $page }}</span>
                                </li>
                            @else
                                <li class="page-item">
                                    <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                                </li>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">
                            <span>Next</span>
                            <i class="ri-arrow-right-s-line"></i>
                        </a>
                    </li>
                @else
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link">
                            <span>Next</span>
                            <i class="ri-arrow-right-s-line"></i>
                        </span>
                    </li>
                @endif
            </ul>
        </div>

        <div class="pagination-jump">
            <label>Jump to page:</label>
            <div class="jump-input-group">
                <input type="number" id="jumpToPage" min="1" max="{{ $paginator->lastPage() }}" 
                       placeholder="{{ $paginator->currentPage() }}">
                <button id="jumpToPageBtn" class="jump-btn">
                    <i class="ri-arrow-right-s-line"></i>
                </button>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Per page select
        const perPageSelect = document.getElementById('perPageSelect');
        if (perPageSelect) {
            perPageSelect.addEventListener('change', function() {
                const url = new URL(window.location.href);
                url.searchParams.set('per_page', this.value);
                url.searchParams.set('page', 1);
                window.location.href = url.toString();
            });
        }

        // Jump to page
        const jumpToPage = document.getElementById('jumpToPage');
        const jumpToPageBtn = document.getElementById('jumpToPageBtn');
        
        function jumpToPageNumber() {
            let page = parseInt(jumpToPage.value);
            const lastPage = {{ $paginator->lastPage() }};
            
            if (isNaN(page)) page = 1;
            if (page < 1) page = 1;
            if (page > lastPage) page = lastPage;
            
            const url = new URL(window.location.href);
            url.searchParams.set('page', page);
            window.location.href = url.toString();
        }
        
        if (jumpToPageBtn) {
            jumpToPageBtn.addEventListener('click', jumpToPageNumber);
        }
        
        if (jumpToPage) {
            jumpToPage.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    jumpToPageNumber();
                }
            });
        }
    });
    </script>
@endif