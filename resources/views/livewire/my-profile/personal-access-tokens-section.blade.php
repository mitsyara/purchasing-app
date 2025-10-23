<div>
    <section class="fi-section fi-section-has-header fi-aside fi-compact">
        <header class="fi-section-header">
            <div class="fi-section-header-text-ctn">
                <h2 class="fi-section-header-heading">
                    {{ __('Personal Access Tokens') }}
                </h2>
                <p class="fi-section-header-description">
                    {{ $this->description ?? '' }}
                </p>
            </div>
        </header>

        <div class="fi-section-content-ctn">
            <div class="fi-sc  fi-sc-has-gap fi-grid  fi-section-content"
                style="--cols-default: repeat(1, minmax(0, 1fr));">

                <div style="--col-span-default: span 1 / span 1;">
                    <div class="fi-sc-component">
                        <div class="fi-sc-flex fi-from-default">
                            <div class="fi-growable">
                                {{ $this->table }}
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>
</div>
