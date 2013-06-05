<div id="search-content">
    <?php if (!$hasText): ?>
        <p><?php echo __('No search inside this document, because there is no text.'); ?></p>
    <?php else: ?>
    <h2><?php echo __('Search inside this document'); ?></h2>
    <form action="#search-content" method="post">
        <input type="hidden" name="action" value="seek">
        <input class="search" type="text" name="words_to_search" size=35 maxlength=100 value="">
        <input type="submit" value="ok" id="submit_search">
    </form>
        <?php if ($hasKeywords): ?>
        <div id="info-result">
            <strong><em>
            <?php if ($countResults == 0):
                echo __('"%s" appears nowhere in the document.', $keywords);
            elseif ($countResults == 1):
                echo __('"%s" appears in one page:', $keywords);
            elseif ($countResults < $maxResults):
                echo __('"%s" appears in %d pages:', $keywords, $countResults);
            else:
                echo __('"%s" appears in more than %d pages:', $keywords, $countResults);
            endif; ?>
            </em></strong><br />
        </div>
            <?php if ($countResults > 0): ?>
        <div id="found-results">
                <?php foreach ($results as $values): ?>
                    <a href="?image=<?php echo $values['currentImage']; ?>#live_book"><?php echo $values['labelPage']; ?></a> :
                    <?php echo $values['highlightText']; ?><br />
                <?php endforeach; ?>
                <?php if ($countResults > $maxResults): ?>
                    <strong><em><?php echo __('Too many results. Next ones are hidden.'); ?></em></strong>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
