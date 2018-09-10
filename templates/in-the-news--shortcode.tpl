<div class="umich-news in-the-news">
    <?php if( count( $newsRes->results ) ): ?>
    <ul class="news-items">
        <? foreach( $newsRes->results as $row ): ?>
        <li><a href="<?=$row->url;?>">
            <span class="outlet"><?=$row->outlet;?></span>
            <span class="title"><?=$row->title;?></span>
        </a></li>
        <? endforeach; ?>
    </ul>
    <?php else: ?>
    <p class="error">No news items available.</p>
    <?php endif; ?>
</div>
