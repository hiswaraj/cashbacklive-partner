<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        <meta name="referrer" content="never" />
        <meta
            name="description"
            content="{{ $title ?? app(App\Settings\GeneralSettings::class)->site_name }}"
        />

        <title>
            {{ $title ?? app(App\Settings\GeneralSettings::class)->site_name }}

        </title>

        <link
            rel="icon"
            type="image/png"
            sizes="50x50"
            href="{{ asset('favicon.png') }}"
        />

        <!-- Styles -->
        <link href="{{ asset('css/old-dev/styles.css') }}" rel="stylesheet" />

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com/" />
        <link
            rel="preconnect"
            href="https://fonts.gstatic.com/"
            crossorigin=""
        />
        <link
            href="{{ asset('css/old-dev/font-devanagari.css') }}"
            rel="stylesheet"
        />
    </head>

    <body data-aos-easing="ease" data-aos-duration="400" data-aos-delay="0">
        <!-- Header -->
        <header class="bg-dark sticky top-0 z-10 py-4 xl:py-6">
            <div class="container">
                <div class="text-center">
                    <a href="#" class="inline-block text-center">
                        <img
                            class="mx-auto max-h-8 xl:max-h-12"
                            src="{{ asset('img/app-logo-full.webp') }}"
                            alt="site logo"
                        />
                    </a>
                </div>
            </div>
        </header>
        <!-- End Header -->
        <main class="relative min-h-[calc(100vh-207px)]">
            <!-- hero left dots -->
            <div class="hero-left dot rounded-full">
                <span
                    class="bols-animation from-blue absolute block rounded-full bg-gradient-to-b to-blue-700"
                />
                <span
                    class="bols-animation from-pink absolute block rounded-full bg-gradient-to-b to-pink-700"
                />
                <span
                    class="bols-animation from-red absolute hidden rounded-full bg-gradient-to-b to-red-700 md:block"
                />
                <span
                    class="bols-animation from-yellow absolute hidden rounded-full bg-gradient-to-b to-yellow-700 md:block"
                />
            </div>
            <!--End hero left dots -->
            <!-- all campaigns section -->
            <section>
                <div class="container">{{ $slot }}</div>
            </section>
            <!-- End all campaigns section -->
            <!-- hero right dots -->
            <div class="absolute top-0 right-0 hidden md:block">
                <div class="hero-right dot rounded-full">
                    <span
                        lass="bols-animation from-yellow absolute block rounded-full bg-gradient-to-b to-yellow-700"
                    />
                    <span
                        class="bols-animation from-red absolute block rounded-full bg-gradient-to-b to-red-700"
                    />
                    <span
                        class="bols-animation from-pink absolute hidden rounded-full bg-gradient-to-b to-pink-700 md:block"
                    />
                    <span
                        class="bols-animation from-blue absolute hidden rounded-full bg-gradient-to-b to-blue-700 md:block"
                    />
                </div>
            </div>
            <!--End hero right dots -->
        </main>

        <!-- campaigns page footer -->
        <footer class="bg-dark-900 py-3 lg:py-5">
            <div class="container">
                <div class="text-xs text-white md:text-lg">
                    <p class="font-medium">Contact For Business Growth</p>
                    <div class="mt-2 flex items-center space-x-3">
                        <div class="group flex flex-shrink-0 items-center">
                            <a href="" target="_blank">
                                <div class="flex-shrink-0">
                                    <img
                                        class="max-h-6 xl:max-h-9"
                                        src="{{ asset('img/ic-email.png') }}"
                                        alt="support@cashbacklive.in"
                                    />
                                </div>
                            </a>
                            <a
                                class="p-2 group-hover:underline"
                                href="mailto:support@cashbacklive.in"
                            >
                                support@cashbacklive.in
                            </a>
                        </div>
                        <div
                            class="group flex flex-shrink-0 items-center space-x-3"
                        >
                            <a
                                href="https://telegram.dog/cashbacklive_official"
                                target="_blank"
                            >
                                <div class="flex-shrink-0">
                                    <img
                                        class="max-h-6 xl:max-h-9"
                                        src="{{ asset('img/ic-telegram.png') }}"
                                        alt=""
                                    />
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
        <!-- End footer -->
    </body>
</html>
