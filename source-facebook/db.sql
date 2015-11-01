CREATE TABLE `ost_facebook_conversation` (
  `facebook_id` varchar(100) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `ost_facebook_thread` (
  `facebook_id` varchar(100) NOT NULL,
  `ticket_thread_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `ost_facebook_conversation`
  ADD PRIMARY KEY (`facebook_id`),
  ADD UNIQUE KEY `ticket_id` (`ticket_id`);

ALTER TABLE `ost_facebook_thread`
  ADD PRIMARY KEY (`facebook_id`);
