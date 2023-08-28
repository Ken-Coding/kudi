@extends("layout.main")


@section("body")
@include("layout.sidebar")
<div class="container">
    <div class="row mt-5 mb-0 mx-3">
        <div class="alert alert-success alert-dismissible fade show success_message" style="display:none;" role="alert">
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <h1>Recipes</h1>
        <hr class="mb-5">
        <div class="col-md-3 mb-0">
            <div class="d-flex" role="search">
                <input class="form-control me-2 search" type="search" placeholder="Search" aria-label="Search">
            </div>
        </div>
    </div>

    <div id="recipes"></div>

    <div class="d-flex justify-content-end mt-5" style="margin-right:1.5rem;">
        <nav aria-label="...">
            <ul class="pagination">
            </ul>
        </nav>
    </div>
</div>
@endsection

@section("script")
<script>
    $(document).ready(function() {
        let currentPage = 1;
        let totalPages = 1;
        fetchData();

        function goToPage(newPage) {
            if (newPage < 1) newPage = 1;
            if (newPage > totalPages) newPage = totalPages;

            currentPage = newPage;
            fetchData();
        }

        function addPaginationControls(ingredients) {
            $(".pagination").html("");

            const isFirstPage = currentPage === 1;
            const isLastPage = currentPage === totalPages;

            const addPaginationControl = (active, pageNumber, label, disabled = false) => {
                $(".pagination").append(`
                    <li class="page-item">
                        <button class="page-link ${disabled ? 'disabled' : ''} ${active === true ? "active-paginate" : "text-dark"}" data-page-number="${pageNumber}" ${disabled ? 'disabled' : ''}>
                            ${label}
                        </button>
                    </li>
                `);
            };

            const addPageNumber = (pageNumber) => {
                const link = ingredients.links[pageNumber];
                addPaginationControl(link.active, pageNumber, link.label);
            };

            addPaginationControl(false, 'prev', '« Previous', currentPage === 1);

            const start = currentPage - 1 + (isLastPage ? -1 : 0);
            const end = currentPage + 1 + (isFirstPage ? 1 : 0);

            for (let i = start; i <= end; i++) {
                if (i < 1 || i > totalPages) continue;
                addPageNumber(i);
            }

            addPaginationControl(false, 'next', 'Next »', currentPage === totalPages);

            $(".pagination li button").click((e) => {
                const pageNumber = e.target.getAttribute('data-page-number');
                if (pageNumber === undefined) return;

                if (pageNumber === 'prev') goToPage(currentPage - 1);
                else if (pageNumber === 'next') goToPage(currentPage + 1);
                else goToPage(Number.parseInt(pageNumber, 10));
            });
        }

        $(document).on("input", ".search", () => goToPage(1));

        function fetchData() {
            const rawSearchQuery = $('.search').val();
            const search = rawSearchQuery.trim().length === 0 ? 'all' : rawSearchQuery;

            $.ajax({
                type: "GET",
                url: `/recipes/fetchData/${search}/?page=${currentPage}`,
                dataType: "json",
                success: function(response) {
                    let html = '';
                    $("#recipes").html("");
                    $.each(response.recipes.data, function(i, recipe) {
                        console.log("ms", recipe.missing_quantity)
                        console.log("d", recipe.diff)
                        console.log("pd", recipe.positive_diff)
                        if (i % 3 === 0) {
                            html += '<div class="row mt-5 mb-0 mx-3">';
                        }

                        html += `
                    <div class="col-md-4 py-2 py-md-0">
                        <div class="card h-100 d-flex flex-column justify-content-between">
                            <div>
                                <img class="card-img-top" style="width: 100%; height: 15rem; object-fit: cover;" src="${recipe.recipe_img}" alt="Card image cap">
                                <div class="card-body">
                                    <div>
                                        <div class="d-flex justify-content-between">
                                            <h5 class="card-title">${recipe.recipe_name}</h5>
                    `;

                        if (recipe.is_favourited > 0) {
                            html += `
                        <img src="{{ asset('img/heart-red.png') }}" data-recipe-id="${recipe.id}" id="favorite-${recipe.id}" class="favorite-red" style="width: 1.5rem; height: 1.5rem;">
                        `;
                        } else {
                            html += `
                        <img src="{{ asset('img/heart-black.png') }}" data-recipe-id="${recipe.id}" id="favorite-${recipe.id}"  class="favorite-black" style="width: 1.5rem; height: 1.5rem;">
                        `;
                        }

                        html += `
                                    </div>
                    `;

                        if (recipe.missing_quantity != 0) {
                            html += `
                            <span class="text-danger">
                                Total ${recipe.missing_quantity} missing ingredient${recipe.missing_quantity > 1 ? 's' : ''}
                            </span>
                        `;
                        }

                        html += `
                                        <p class="card-text mt-3">
                                            ${recipe.description}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <a href="/recipes/detail/${recipe.id}" class="btn btn-secondary mx-2 mb-2">
                                <img src="{{asset('img/view.png')}}" alt="" style="width: 1.5rem; margin-right: 0.3rem;">
                                <span>View Recipe</span>
                            </a>
                        </div>
                    </div>
                    `;

                        if (i % 3 === 2 || i === response.recipes.length - 1) {
                            html += '</div>';
                        }
                    });

                    $("#recipes").append(html);
                    addPaginationControls(response.recipes);
                }

            })
        }

        $(document).on("click", ".favorite-black", function() {
            const recipeId = {
                "recipe_id": $(this).data("recipe-id")
            };

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.ajax({
                type: "POST",
                url: `/favorites`,
                data: recipeId,
                dataType: "json",
                success: function(response, _, xhr) {
                    if (xhr.status === 200) {
                        $(`#favorite-${recipeId.recipe_id}`).attr('src', '<?= asset('img/heart-red.png'); ?>')
                        fetchData();
                    }
                },
            })
        })

        $(document).on("click", ".favorite-red", function() {
            const recipeId = $(this).data("recipe-id");

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.ajax({
                type: "DELETE",
                url: `/favorites/${recipeId}`,
                dataType: "json",
                success: function(response, _, xhr) {
                    if (xhr.status === 200) {
                        $(`#favorite-${recipeId}`).attr('src', '<?= asset('img/heart-black.png'); ?>')
                        fetchData();
                    }
                },
            })
        })
    })
</script>
@endsection