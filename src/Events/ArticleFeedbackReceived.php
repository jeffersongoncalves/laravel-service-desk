<?php

declare(strict_types=1);

namespace JeffersonGoncalves\ServiceDesk\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JeffersonGoncalves\ServiceDesk\Models\KbArticle;
use JeffersonGoncalves\ServiceDesk\Models\KbArticleFeedback;

class ArticleFeedbackReceived
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly KbArticle $article,
        public readonly KbArticleFeedback $feedback,
    ) {}
}
