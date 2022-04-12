<div class="<?=$instance['className'];?>">
    <?php if( count( $newsRes->results ) ): ?>
    <ul class="news-items">
        <?php foreach( $newsRes->results as $row ): ?>
        <li><a href="<?=$row->url;?>">
            <?php if( $instance['showDate'] ): ?>
            <span class="date"><?=date( 'F j, Y', strtotime( $row->date ) );?></span>
            <?php endif; ?>
            <span class="outlet"><?=$row->outlet;?></span>
            <span class="title"><?=$row->title;?></span>
        </a></li>
        <?php endforeach; ?>
    </ul>
    <?php
    if( $instance['paginate'] ) {
        print_r( $newRes->meta );
        echo paginate_links(array(
            'type'    => 'list',
            'base'    => get_permalink( get_queried_object() ) .'%_%',
            'format'  => '?'. $instance['pagevar'] .'=%#%',
            'current' => max( 1, @$_GET[ $instance['pagevar'] ] ),
            'total'   => ceil( $newsRes->meta->total / $newsRes->meta->max )
        ));
    }
    else {
        echo '<p class="more"><a href="/in-the-news/">More In The News</a></p>';
    }
    ?>
    <?php else: ?>
    <p class="error">No news items available.</p>
    <?php endif; ?>
</div>
