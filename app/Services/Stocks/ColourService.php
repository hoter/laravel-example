<?php namespace App\Services\Stocks;

use DB;

class ColourService
{

    // -----------------------------------------------------------------------------------------------------------------
    // properties

    protected $names;

    protected $colours;

    protected $lookups;

    protected $matcher;


    // -----------------------------------------------------------------------------------------------------------------
    // instantiation

    public function __construct()
    {
        $colours = config('constants.colours');
        $lookups = [];

        foreach ($colours as $colour => $variants)
        {
            $lookups[$colour] = $colour;
            if ($variants)
            {
                foreach ($variants as $variant)
                {
                    $lookups[$variant] = $colour;
                }
            }
        }

        $this->lookups = $lookups;
        $this->names   = array_keys($colours);
        $this->colours = array_keys($lookups);
        $this->matcher = '%\b(' . implode('|', $this->colours) . ')\b%iU';
    }


    // -----------------------------------------------------------------------------------------------------------------
    // methods

    /**
     * Parse a supplied colour into a generic colour
     *
     * @param string $colour
     * @return mixed|string
     */
    public function parse($colour)
    {
       $colour = strtolower($colour);
        // need to use preg_match_all to get all matches, then rank them via the lookups order
        preg_match_all($this->matcher, $colour, $matches);
        if (count($matches[0]))
        {
            $matches = $matches[0];
            //$first_val = $matches[0];
            $values  = array_intersect_key($this->lookups, array_flip($matches));
            return array_shift($values);

//            if(array_search($first_val, $values)) {
//                return $first_val;
//            } else {
//                return array_shift($values);
//            }
            //return $this->lookups[$matches[1]];
        }
        return 'other';
    }

    public function seedStocks ()
    {
        // collate ids for colours
        $groups = DB::table('stocks')
            ->get(['id', 'colour_spec'])
            ->each(function ($row)  {
                $row->colour = $this->parse($row->colour_spec);
            })
            ->groupBy('colour')
            ->map(function ($collection) {
                return $collection->pluck('id')->toArray();
            });

        // update tables
        $groups
            ->each(function ($ids, $colour) {
                DB::table('stocks')
                    ->whereIn('id', $ids)
                    ->update(['colour' => $colour]);
            });
    }

}