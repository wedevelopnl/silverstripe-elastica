<div class="block">
    <div class="container">
        <div class="row">
            <main>
                SearchQuery: <strong>$Query</strong><br/>

                CountResults: <strong>$Results.CountResults</strong><br/>
                MoreThanOnePage: <strong>$Results.MoreThanOnePage</strong><br/>
                NotFirstPage: <strong>$Results.NotFirstPage</strong><br/>
                NotLastPage: <strong>$Results.NotLastPage</strong><br/>
                PrevLink: <strong>$Results.PrevLink</strong><br/>
                NextLink: <strong>$Results.NextLink</strong><br/>

                <% if $Results.Data%>
                    <ul class="list list-group">
                        <% loop $Results.Data %>
                            <li>
                                <div>
                                    <h2 class="heading-xs no-margin">
                                        <a href="$Url" class="link-color-dark">$Title</a>
                                    </h2>
                                    <p class="no-margin">
                                        $Content.LimitCharacters(200)
                                        <a href="$Url" title="Lees meer over &quot;{$Title}&quot;" class="link">
                                            Lees meer <i class="far fa-arrow-right item-last"></i>
                                        </a>
                                    </p>
                                </div>
                            </li>
                        <% end_loop %>
                    </ul>

                    <% if $Results.MoreThanOnePage %>
                        <ul class="pagination">
                            <% if $Results.NotFirstPage %>
                                <li>
                                    <a href="$Results.PrevLink" title="Bekijk de vorige resultaten">
                                        <span><-</span>
                                    </a>
                                </li>
                            <% end_if %>
                            <% loop $Results.PageLinks %>
                                <li>
                                    <% if $CurrentBool %>
                                        <li class="active">
                                            <span>$PageNum</span>
                                        </li>
                                    <% else %>
                                        <% if $Link %>
                                            <li>
                                                <a href="$Link" title="Ga naar pagina $PageNum">
                                                    $PageNum
                                                </a>
                                            </li>
                                        <% else %>
                                            <span>...</span>
                                        <% end_if %>
                                    <% end_if %>
                                </li>
                            <% end_loop %>

                            <% if $Results.NotLastPage %>
                                <li>
                                    <a href="$Results.NextLink" title="Ga naar de volgende pagina">
                                        <span>-></span>
                                    </a>
                                </li>
                            <% end_if %>
                        </ul>
                    <% end_if %>
                <% end_if %>
            </main>
        </div>
    </div>
</div>
