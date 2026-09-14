<?php

namespace App\Services;

use App\Models\Hymn;
use App\Models\HymnAdditionRequest;
use App\Models\HymnSource;
use App\Support\HymnSourceResolver;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class HymnAdditionRequestReviewService
{
    public function approveNew(
        HymnAdditionRequest $request,
        ?int $reviewedById
    ): Hymn {
        return DB::transaction(
            function () use (
                $request,
                $reviewedById
            ): Hymn {
                $request =
                    $this->lockPendingRequest(
                        $request
                    );

                $title =
                    trim(
                        (string) $request->title
                    );

                if ($title === '') {
                    throw new RuntimeException(
                        'The requested hymn has no title.'
                    );
                }

                $hymn =
                    Hymn::query()
                        ->create([
                            'source' =>
                                'manual',

                            'source_id' =>
                                'request:'
                                . $request->id,

                            'title' =>
                                $title,

                            'language' =>
                                filled(
                                    $request->language
                                )
                                    ? trim(
                                        (string)
                                        $request->language
                                    )
                                    : null,

                            'lyrics' =>
                                filled(
                                    $request->lyrics
                                )
                                    ? trim(
                                        (string)
                                        $request->lyrics
                                    )
                                    : null,

                            'source_url' =>
                                filled(
                                    $request->source_url
                                )
                                    ? trim(
                                        (string)
                                        $request->source_url
                                    )
                                    : null,

                            'is_active' =>
                                true,

                            'last_synced_at' =>
                                null,
                        ]);

                $this->attachRequestSource(
                    $hymn,
                    $request
                );

                $this->markApproved(
                    $request,
                    $hymn,
                    $reviewedById
                );

                return $hymn;
            }
        );
    }

    public function approveLink(
        HymnAdditionRequest $request,
        Hymn $hymn,
        ?int $reviewedById
    ): Hymn {
        return DB::transaction(
            function () use (
                $request,
                $hymn,
                $reviewedById
            ): Hymn {
                $request =
                    $this->lockPendingRequest(
                        $request
                    );

                $hymn =
                    Hymn::query()
                        ->where(
                            'is_active',
                            true
                        )
                        ->findOrFail(
                            $hymn->id
                        );

                $this->attachRequestSource(
                    $hymn,
                    $request
                );

                $this->markApproved(
                    $request,
                    $hymn,
                    $reviewedById
                );

                return $hymn;
            }
        );
    }

    public function reject(
        HymnAdditionRequest $request,
        ?int $reviewedById
    ): void {
        DB::transaction(
            function () use (
                $request,
                $reviewedById
            ): void {
                $request =
                    $this->lockPendingRequest(
                        $request
                    );

                $request->forceFill([
                    'status' =>
                        HymnAdditionRequest::STATUS_REJECTED,

                    'reviewed_by_id' =>
                        $reviewedById,

                    'reviewed_at' =>
                        now(),

                    'created_hymn_id' =>
                        null,
                ])->save();
            }
        );
    }

    private function lockPendingRequest(
        HymnAdditionRequest $request
    ): HymnAdditionRequest {
        $request =
            HymnAdditionRequest::query()
                ->lockForUpdate()
                ->findOrFail(
                    $request->id
                );

        if (
            $request->status
            !== HymnAdditionRequest::STATUS_PENDING
        ) {
            throw new RuntimeException(
                'This Hymn Addition Request '
                . 'has already been reviewed.'
            );
        }

        return $request;
    }

    private function markApproved(
        HymnAdditionRequest $request,
        Hymn $hymn,
        ?int $reviewedById
    ): void {
        /*
         * created_hymn_id currently represents
         * the canonical Hymn resolved by approval.
         * It can be newly created or pre-existing.
         */
        $request->forceFill([
            'status' =>
                HymnAdditionRequest::STATUS_APPROVED,

            'reviewed_by_id' =>
                $reviewedById,

            'reviewed_at' =>
                now(),

            'created_hymn_id' =>
                $hymn->id,
        ])->save();
    }

    private function attachRequestSource(
        Hymn $hymn,
        HymnAdditionRequest $request
    ): void {
        $url =
            trim(
                (string) $request->source_url
            );

        if ($url === '') {
            return;
        }

        $provider =
            HymnSourceResolver::providerForUrl(
                $url
            );

        HymnSource::query()
            ->firstOrCreate(
                [
                    'hymn_id' =>
                        $hymn->id,

                    'source_url' =>
                        $url,
                ],
                [
                    'provider' =>
                        $provider,

                    'source_type' =>
                        HymnSourceResolver
                            ::sourceTypeForProvider(
                                $provider
                            ),

                    'external_id' =>
                        null,

                    'label' =>
                        HymnSourceResolver
                            ::labelForProvider(
                                $provider
                            ),

                    'metadata' => [
                        'hymn_addition_request_id' =>
                            $request->id,
                    ],
                ]
            );
    }
}
