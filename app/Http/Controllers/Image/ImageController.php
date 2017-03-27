<?php

    namespace App\Http\Controllers\Image;

    use App\Domain;
    use App\Http\Controllers\Controller;
    use App\Image;
    use App\User;
    use Illuminate\Database\Query\Builder;
    use Illuminate\Support\Facades\Cache;
    use Request;

    /**
     * Class ImageController
     *
     * @package App\Http\Controllers\Image
     */
    class ImageController extends Controller {

        /**
         * @param Request $request
         * @param         $imageUrl
         *
         * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
         */
        function getImageFromUrl(Request $request, $imageUrl, $imageExtension) {

            /** @var Builder $builder */
            $builder = Image::whereUrlName($imageUrl);
            /** @var Image $image */
            $image = $builder->firstOrFail();

            return response(\Storage::get($image->file_path . '/' . $image->getBaseName()), 200, [
                'Content-Type' => $image->filetype
            ]);
        }

        /**
         * @param Request $request
         * @param         $imageUrl
         *
         * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
         */
        function getPreviewPage(Request $request, $imageUrl) {

            list($image, $domain, $author) = $this->getImageMeta($imageUrl);

            return view('image.preview', [
                'image'  => $image,
                'domain' => $domain,
                'author' => $author
            ]);
        }

        /**
         * @param $imageUrl
         *
         * @return array
         */
        function getImageMeta($imageUrl) {
            if (Cache::has('image.' . $imageUrl . '.preview')) {
                $image  = Cache::get('image.' . $imageUrl . '.preview');
                $domain = Cache::get('image.' . $imageUrl . '.domain');
                $author = Cache::get('image.' . $imageUrl . '.user');
            } else {
                /** @var Builder $builder */
                $builder = Image::whereUrlName($imageUrl);
                /** @var Image $image */
                $image  = $builder->firstOrFail();
                $domain = $image->domain()->getResults();
                $author = $image->user()->getResults();
                Cache::put('image.' . $imageUrl . '.preview', $image, 120);
                Cache::put('image.' . $imageUrl . '.domain', $domain, 120);
                Cache::put('image.' . $imageUrl . '.user', $author, 120);
            }
            return [$image, $domain, $author];
        }

        /**
         * @param Request $request
         * @param         $imageUrl
         *
         * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
         */
        function getOEmbed(Request $request, $imageUrl) {
            /** @var Image $image */
            /** @var Domain $domain */
            /** @var User $author */

            list($image, $domain, $author) = $this->getImageMeta($imageUrl);
            $response = [
                'type'          => 'photo',
                'version'       => 1.0,
                'title'         => $image->name . ' | ' . $domain->domain,
                'url'           => $image->getUrlToImage(),
                'provider_name' => config('app.name'),
                'provider_url'  => config('app.url'),
                'width'         => $image->width(),
                'height'        => $image->height(),
                'mime'          => $image->filetype,
                'author_name'   => $author->embed_name,
                'author_url'    => $author->embed_name_url
            ];

            return response($response);

        }

        /**
         * @param Request $request
         * @param         $imageUrl
         *
         * @return \Illuminate\Http\Response
         */
        function getThumbnail(Request $request, $imageUrl) {
            /** @var Image $image */
            list($image, $domain, $author) = $this->getImageMeta($imageUrl);
            return $image->getThumbnail();
        }

    }